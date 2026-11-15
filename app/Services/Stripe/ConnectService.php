<?php

namespace App\Services\Stripe;

use App\Models\Organiser;
use Stripe\StripeClient;
use Stripe\V2\Core\Account;

class ConnectService
{
    /**
     * Accounts v2 returns these properties as null unless they are requested.
     *
     * @var array<int, string>
     */
    private const INCLUDES = [
        'configuration.merchant',
        'configuration.recipient',
        'requirements',
        'identity',
        'defaults',
    ];

    private const CARD_PAYMENTS = 'configuration.merchant.capabilities.card_payments';

    public function __construct(private readonly StripeClient $stripe) {}

    public function createAccount(Organiser $organiser): string
    {
        if ($organiser->stripe_account_id) {
            return $organiser->stripe_account_id;
        }

        $account = $this->stripe->v2->core->accounts->create([
            'contact_email' => $organiser->email,
            'display_name' => $organiser->name,
            'dashboard' => 'express',
            'identity' => [
                'country' => strtolower((string) config('services.stripe.connect_country', 'GB')),
            ],
            'configuration' => [
                'merchant' => [
                    'capabilities' => [
                        'card_payments' => ['requested' => true],
                    ],
                ],
                'recipient' => [
                    'capabilities' => [
                        'stripe_balance' => [
                            'stripe_transfers' => ['requested' => true],
                        ],
                    ],
                ],
            ],
            'defaults' => [
                'responsibilities' => [
                    // Stripe requires both to be "application" for Express dashboard accounts.
                    'fees_collector' => 'application',
                    'losses_collector' => 'application',
                ],
            ],
            'metadata' => [
                'organiser_id' => (string) $organiser->id,
            ],
            'include' => self::INCLUDES,
        ], [
            'idempotency_key' => 'organiser-account-'.$organiser->id,
        ]);

        $organiser->forceFill(['stripe_account_id' => $account->id])->save();

        $this->applyAccount($organiser, $account);

        return $account->id;
    }

    public function onboardingUrl(Organiser $organiser): string
    {
        $accountId = $organiser->stripe_account_id ?: $this->createAccount($organiser);

        $link = $this->stripe->v2->core->accountLinks->create([
            'account' => $accountId,
            'use_case' => [
                'type' => 'account_onboarding',
                'account_onboarding' => [
                    'refresh_url' => route('stripe.connect.refresh', $organiser),
                    'return_url' => route('stripe.connect.return', $organiser),
                ],
            ],
        ]);

        return $link->url;
    }

    public function refreshStatus(Organiser $organiser): void
    {
        if (! $organiser->stripe_account_id) {
            return;
        }

        $this->applyAccount($organiser, $this->retrieve($organiser));
    }

    /**
     * @param  Account|array<string, mixed>  $account
     */
    public function applyAccount(Organiser $organiser, Account|array $account): void
    {
        $chargesEnabled = $this->capabilityStatus($account, self::CARD_PAYMENTS) === 'active';
        $requirements = data_get($account, 'requirements');
        $detailsSubmitted = $requirements !== null && ! $this->hasOutstandingRequirements($account);

        $organiser->forceFill([
            'stripe_charges_enabled' => $chargesEnabled,
            'stripe_details_submitted' => $detailsSubmitted,
            'stripe_onboarded_at' => $chargesEnabled && $detailsSubmitted
                ? ($organiser->stripe_onboarded_at ?? now())
                : null,
        ])->save();
    }

    public function dashboardUrl(Organiser $organiser): string
    {
        // There is no Accounts v2 login link endpoint; v1 endpoints accept v2 account IDs.
        $link = $this->stripe->accounts->createLoginLink($organiser->stripe_account_id);

        return $link->url;
    }

    /**
     * @return array{
     *     currently_due: array<int, array{description: string, awaiting_action_from: ?string, errors: array<int, string>}>,
     *     eventually_due: array<int, array{description: string, awaiting_action_from: ?string, errors: array<int, string>}>,
     *     disabled_reason: ?string
     * }
     */
    public function requirements(Organiser $organiser): array
    {
        if (! $organiser->stripe_account_id) {
            return ['currently_due' => [], 'eventually_due' => [], 'disabled_reason' => null];
        }

        $account = $this->retrieve($organiser);

        return [
            'currently_due' => $this->requirementEntries($account, ['currently_due', 'past_due']),
            'eventually_due' => $this->requirementEntries($account, ['eventually_due']),
            'disabled_reason' => $this->disabledReason($account),
        ];
    }

    private function retrieve(Organiser $organiser): Account
    {
        return $this->stripe->v2->core->accounts->retrieve($organiser->stripe_account_id, [
            'include' => self::INCLUDES,
        ]);
    }

    /**
     * @param  Account|array<string, mixed>  $account
     */
    private function capabilityStatus(Account|array $account, string $capability): ?string
    {
        $status = data_get($account, $capability.'.status');

        return is_string($status) ? $status : null;
    }

    /**
     * @param  Account|array<string, mixed>  $account
     * @param  array<int, string>  $deadlines
     * @return array<int, array{description: string, awaiting_action_from: ?string, errors: array<int, string>}>
     */
    private function requirementEntries(Account|array $account, array $deadlines): array
    {
        return collect(data_get($account, 'requirements.entries') ?? [])
            ->filter(fn ($entry) => in_array(data_get($entry, 'minimum_deadline.status'), $deadlines, true))
            ->map(fn ($entry) => [
                'description' => (string) data_get($entry, 'description', 'Additional information required'),
                'awaiting_action_from' => data_get($entry, 'awaiting_action_from'),
                'errors' => collect(data_get($entry, 'errors') ?? [])
                    ->pluck('code')
                    ->filter()
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Account|array<string, mixed>  $account
     */
    private function hasOutstandingRequirements(Account|array $account): bool
    {
        return collect(data_get($account, 'requirements.entries') ?? [])
            ->contains(fn ($entry) => data_get($entry, 'awaiting_action_from') === 'user'
                && in_array(data_get($entry, 'minimum_deadline.status'), ['currently_due', 'past_due'], true));
    }

    /**
     * @param  Account|array<string, mixed>  $account
     */
    private function disabledReason(Account|array $account): ?string
    {
        if ($this->capabilityStatus($account, self::CARD_PAYMENTS) === 'active') {
            return null;
        }

        return collect(data_get($account, self::CARD_PAYMENTS.'.status_details') ?? [])
            ->pluck('code')
            ->filter()
            ->first();
    }
}

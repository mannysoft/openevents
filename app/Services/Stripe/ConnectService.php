<?php

namespace App\Services\Stripe;

use App\Models\Organiser;
use Stripe\Account;
use Stripe\StripeClient;

class ConnectService
{
    public function __construct(private readonly StripeClient $stripe)
    {
    }

    public function createAccount(Organiser $organiser): string
    {
        if ($organiser->stripe_account_id) {
            return $organiser->stripe_account_id;
        }

        $account = $this->stripe->accounts->create([
            'type' => 'express',
            'country' => config('services.stripe.connect_country', 'GB'),
            'email' => $organiser->email,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
            'business_profile' => [
                'name' => $organiser->name,
            ],
            'metadata' => [
                'organiser_id' => (string) $organiser->id,
            ],
        ], [
            'idempotency_key' => 'organiser-account-'.$organiser->id,
        ]);

        $organiser->forceFill(['stripe_account_id' => $account->id])->save();

        return $account->id;
    }

    public function onboardingUrl(Organiser $organiser): string
    {
        $accountId = $organiser->stripe_account_id ?: $this->createAccount($organiser);

        $link = $this->stripe->accountLinks->create([
            'account' => $accountId,
            'type' => 'account_onboarding',
            'refresh_url' => route('stripe.connect.refresh', $organiser),
            'return_url' => route('stripe.connect.return', $organiser),
        ]);

        return $link->url;
    }

    public function refreshStatus(Organiser $organiser): void
    {
        if (! $organiser->stripe_account_id) {
            return;
        }

        $account = $this->stripe->accounts->retrieve($organiser->stripe_account_id);

        $this->applyAccount($organiser, $account);
    }

    /**
     * @param  Account|array<string, mixed>  $account
     */
    public function applyAccount(Organiser $organiser, Account|array $account): void
    {
        $chargesEnabled = (bool) data_get($account, 'charges_enabled');
        $detailsSubmitted = (bool) data_get($account, 'details_submitted');

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
        $link = $this->stripe->accounts->createLoginLink($organiser->stripe_account_id);

        return $link->url;
    }

    /**
     * @return array{currently_due: array<int, string>, eventually_due: array<int, string>, disabled_reason: ?string}
     */
    public function requirements(Organiser $organiser): array
    {
        if (! $organiser->stripe_account_id) {
            return ['currently_due' => [], 'eventually_due' => [], 'disabled_reason' => null];
        }

        $account = $this->stripe->accounts->retrieve($organiser->stripe_account_id);
        $requirements = $account->requirements;

        return [
            'currently_due' => $requirements->currently_due ?? [],
            'eventually_due' => $requirements->eventually_due ?? [],
            'disabled_reason' => $requirements->disabled_reason ?? null,
        ];
    }
}

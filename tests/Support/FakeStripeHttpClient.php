<?php

namespace Tests\Support;

use Stripe\HttpClient\ClientInterface;

/**
 * Stands in for Stripe's HTTP client so the real SDK runs without network calls.
 * Install with Stripe\ApiRequestor::setHttpClient().
 */
class FakeStripeHttpClient implements ClientInterface
{
    /** @var array<int, array{method: string, path: string, params: array}> */
    public array $requests = [];

    /** @var array<string, array<string, mixed>> */
    public array $accounts = [];

    /** @var array<string, array<string, mixed>> */
    public array $intents = [];

    /** @var array<string, array<string, mixed>> */
    public array $refunds = [];

    public string $cardPaymentsStatus = 'active';

    public string $stripeTransfersStatus = 'active';

    /** @var array<int, array{code: string, resolution: string}> */
    public array $cardPaymentsStatusDetails = [];

    /** @var array<int, array{awaiting_action_from: string, description: string, minimum_deadline: array{status: string}, errors?: array<int, array{code: string, description: string}>}> */
    public array $requirementEntries = [];

    public bool $failAccountRetrieve = false;

    private int $sequence = 0;

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        $path = (string) parse_url($absUrl, PHP_URL_PATH);
        $params = is_array($params) ? $params : [];

        $this->requests[] = ['method' => $method, 'path' => $path, 'params' => $params];

        if ($method === 'post' && $path === '/v2/core/accounts') {
            $id = 'acct_fake'.(++$this->sequence);

            $this->accounts[$id] = $this->v2Account($id);

            return $this->respond($this->accounts[$id]);
        }

        if ($method === 'get' && preg_match('#^/v2/core/accounts/([^/]+)$#', $path, $matches) === 1) {
            if ($this->failAccountRetrieve) {
                return $this->respond([
                    'error' => [
                        'type' => 'invalid_request_error',
                        'message' => 'Account retrieve failed.',
                    ],
                ], 400);
            }

            $this->accounts[$matches[1]] = $this->v2Account($matches[1]);

            return $this->respond($this->accounts[$matches[1]]);
        }

        if ($method === 'post' && $path === '/v2/core/account_links') {
            return $this->respond([
                'object' => 'v2.core.account_link',
                'account' => $params['account'] ?? null,
                'created' => '2026-01-01T00:00:00.000Z',
                'expires_at' => '2026-01-01T00:05:00.000Z',
                'livemode' => false,
                'url' => 'https://connect.stripe.test/'.($params['account'] ?? ''),
                'use_case' => $params['use_case'] ?? [],
            ]);
        }

        if ($method === 'post' && preg_match('#^/v1/accounts/([^/]+)/login_links$#', $path, $matches) === 1) {
            return $this->respond([
                'object' => 'login_link',
                'url' => 'https://dashboard.stripe.test/'.$matches[1],
            ]);
        }

        if ($method === 'post' && $path === '/v1/payment_intents') {
            $intent = [
                'id' => 'pi_fake'.(++$this->sequence),
                'object' => 'payment_intent',
                'amount' => $params['amount'] ?? 0,
                'currency' => $params['currency'] ?? 'gbp',
                'status' => 'requires_payment_method',
                'client_secret' => 'pi_fake_secret',
                'application_fee_amount' => $params['application_fee_amount'] ?? null,
                'transfer_data' => $params['transfer_data'] ?? null,
                'metadata' => $params['metadata'] ?? [],
                'receipt_email' => $params['receipt_email'] ?? null,
            ];

            $this->intents[$intent['id']] = $intent;

            return $this->respond($intent);
        }

        if ($method === 'post' && $path === '/v1/refunds') {
            $refund = [
                'id' => 're_fake'.(++$this->sequence),
                'object' => 'refund',
                'payment_intent' => $params['payment_intent'] ?? null,
                'amount' => $params['amount'] ?? 0,
                'refund_application_fee' => filter_var($params['refund_application_fee'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'reverse_transfer' => filter_var($params['reverse_transfer'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'status' => 'succeeded',
            ];

            $this->refunds[$refund['id']] = $refund;

            return $this->respond($refund);
        }

        return $this->respond([
            'error' => [
                'type' => 'invalid_request_error',
                'message' => "Unhandled fake Stripe request: {$method} {$path}",
            ],
        ], 400);
    }

    /**
     * @return array<string, mixed>
     */
    private function v2Account(string $id): array
    {
        return [
            'id' => $id,
            'object' => 'v2.core.account',
            'dashboard' => 'express',
            'contact_email' => null,
            'display_name' => null,
            'configuration' => [
                'merchant' => [
                    'capabilities' => [
                        'card_payments' => [
                            'status' => $this->cardPaymentsStatus,
                            'status_details' => $this->cardPaymentsStatusDetails,
                        ],
                        'stripe_balance' => [
                            'payouts' => ['status' => $this->cardPaymentsStatus, 'status_details' => []],
                        ],
                    ],
                ],
                'recipient' => [
                    'capabilities' => [
                        'stripe_balance' => [
                            'stripe_transfers' => ['status' => $this->stripeTransfersStatus, 'status_details' => []],
                        ],
                    ],
                ],
            ],
            'requirements' => ['entries' => $this->requirementEntries],
            'defaults' => [
                'responsibilities' => [
                    'fees_collector' => 'application',
                    'losses_collector' => 'application',
                ],
            ],
        ];
    }

    /**
     * @return array{0: string, 1: int, 2: array<int, string>}
     */
    private function respond(array $payload, int $status = 200): array
    {
        return [json_encode($payload), $status, []];
    }
}

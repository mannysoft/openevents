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

    public bool $chargesEnabled = true;

    public bool $detailsSubmitted = true;

    private int $sequence = 0;

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        $path = (string) parse_url($absUrl, PHP_URL_PATH);
        $params = is_array($params) ? $params : [];

        $this->requests[] = ['method' => $method, 'path' => $path, 'params' => $params];

        if ($method === 'post' && $path === '/v1/accounts') {
            $id = 'acct_fake'.(++$this->sequence);

            $this->accounts[$id] = [
                'id' => $id,
                'object' => 'account',
                'type' => $params['type'] ?? 'express',
                'email' => $params['email'] ?? null,
                'country' => $params['country'] ?? 'GB',
                'charges_enabled' => $this->chargesEnabled,
                'details_submitted' => $this->detailsSubmitted,
                'requirements' => [
                    'currently_due' => [],
                    'eventually_due' => [],
                    'disabled_reason' => null,
                ],
            ];

            return $this->respond($this->accounts[$id]);
        }

        if ($method === 'get' && preg_match('#^/v1/accounts/([^/]+)$#', $path, $matches) === 1) {
            $account = $this->accounts[$matches[1]] ?? [
                'id' => $matches[1],
                'object' => 'account',
                'type' => 'express',
                'requirements' => ['currently_due' => [], 'eventually_due' => [], 'disabled_reason' => null],
            ];

            $account['charges_enabled'] = $this->chargesEnabled;
            $account['details_submitted'] = $this->detailsSubmitted;

            $this->accounts[$matches[1]] = $account;

            return $this->respond($account);
        }

        if ($method === 'post' && $path === '/v1/account_links') {
            return $this->respond([
                'object' => 'account_link',
                'url' => 'https://connect.stripe.test/'.$params['account'],
                'expires_at' => time() + 300,
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
                'refund_application_fee' => (bool) ($params['refund_application_fee'] ?? false),
                'reverse_transfer' => (bool) ($params['reverse_transfer'] ?? false),
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
     * @return array{0: string, 1: int, 2: array<int, string>}
     */
    private function respond(array $payload, int $status = 200): array
    {
        return [json_encode($payload), $status, []];
    }
}

<?php

namespace Tests\Support;

use Stripe\HttpClient\ClientInterface;

/**
 * Stands in for Stripe's HTTP client, so the real SDK (paging, expansion, error mapping) runs
 * with no network. Install with Stripe\ApiRequestor::setHttpClient() and remove in tearDown.
 *
 * Accounts are keyed 'platform' or by their acct_ id, read from the Stripe-Account header.
 */
class FakeStripeHttpClient implements ClientInterface
{
    /** @var array<int, array{method: string, path: string, account: string, params: array}> */
    public array $requests = [];

    /** @var array<string, array<int, array>> PaymentIntents per account, newest first. */
    public array $intents = [];

    /** @var array<string, array{0: int, 1: string, 2: string}> HTTP status, error type, message. */
    public array $failures = [];

    /** @var array<string, array<int, array>> What a PaymentIntent search returns, per account. */
    public array $searchResults = [];

    /**
     * @var array<string, array<int, array>> Checkout Sessions per account, newest first, each
     *                                       with its payment_intent as a full PaymentIntent array.
     */
    public array $sessions = [];

    /** @var array<string, array{0: int, 1: string, 2: string}> Failures for Checkout Session calls only. */
    public array $sessionFailures = [];

    /** Page size used for list calls, whatever limit is asked for (to exercise paging). */
    public ?int $pageSize = null;

    private int $created = 0;

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1')
    {
        $path = (string) parse_url($absUrl, PHP_URL_PATH);
        $account = 'platform';

        foreach ($headers as $header) {
            if (stripos($header, 'Stripe-Account:') === 0) {
                $account = trim(substr($header, strlen('Stripe-Account:')));
            }
        }

        $params = is_array($params) ? $params : [];
        $this->requests[] = ['method' => $method, 'path' => $path, 'account' => $account, 'params' => $params];

        if (isset($this->failures[$account])) {
            [$status, $type, $message] = $this->failures[$account];

            return [json_encode(['error' => ['type' => $type, 'message' => $message]]), $status, []];
        }

        if ($method === 'get' && $path === '/v1/payment_intents') {
            return $this->listIntents($account, $params);
        }

        if ($method === 'get' && $path === '/v1/checkout/sessions') {
            if (isset($this->sessionFailures[$account])) {
                [$status, $type, $message] = $this->sessionFailures[$account];

                return [json_encode(['error' => ['type' => $type, 'message' => $message]]), $status, []];
            }

            return $this->listSessions($account, $params);
        }

        if ($method === 'get' && $path === '/v1/payment_intents/search') {
            return $this->json([
                'object' => 'search_result',
                'url' => '/v1/payment_intents/search',
                'data' => $this->searchResults[$account] ?? [],
                'has_more' => false,
                'next_page' => null,
            ]);
        }

        if ($method === 'post' && $path === '/v1/payment_intents') {
            $id = 'pi_fake_created_'.(++$this->created);

            return $this->json([
                'id' => $id,
                'object' => 'payment_intent',
                'client_secret' => $id.'_secret_fake',
                'status' => 'requires_payment_method',
                'metadata' => $params['metadata'] ?? [],
            ]);
        }

        if ($method === 'post' && preg_match('#^/v1/payment_intents/([^/]+)$#', $path, $m)) {
            return $this->json([
                'id' => $m[1],
                'object' => 'payment_intent',
                'metadata' => $params['metadata'] ?? [],
            ]);
        }

        return [json_encode(['error' => ['type' => 'invalid_request_error', 'message' => 'Unexpected request in test: '.$method.' '.$path]]), 404, []];
    }

    /**
     * @return array<int, array{method: string, path: string, account: string, params: array}>
     */
    public function requestsTo(string $method, string $pathPattern): array
    {
        return array_values(array_filter(
            $this->requests,
            fn (array $r) => $r['method'] === $method && preg_match($pathPattern, $r['path'])
        ));
    }

    private function listIntents(string $account, array $params): array
    {
        $gte = $params['created']['gte'] ?? PHP_INT_MIN;
        $lte = $params['created']['lte'] ?? PHP_INT_MAX;

        $all = array_values(array_filter(
            $this->intents[$account] ?? [],
            fn (array $pi) => $pi['created'] >= $gte && $pi['created'] <= $lte
        ));

        $offset = 0;

        if (isset($params['starting_after'])) {
            foreach ($all as $i => $pi) {
                if ($pi['id'] === $params['starting_after']) {
                    $offset = $i + 1;
                }
            }
        }

        $size = $this->pageSize ?? (int) ($params['limit'] ?? 10);
        $page = array_slice($all, $offset, $size);

        // Unexpanded, latest_charge is only an id.
        if (! in_array('data.latest_charge', $params['expand'] ?? [], true)) {
            $page = array_map(function (array $pi) {
                $pi['latest_charge'] = is_array($pi['latest_charge']) ? $pi['latest_charge']['id'] : $pi['latest_charge'];

                return $pi;
            }, $page);
        }

        return $this->json([
            'object' => 'list',
            'url' => '/v1/payment_intents',
            'data' => $page,
            'has_more' => $offset + $size < count($all),
        ]);
    }

    /**
     * Filters as Stripe does on created, status and payment_intent; payment_intent is expanded
     * (with its latest_charge) only when asked for.
     */
    private function listSessions(string $account, array $params): array
    {
        $gte = $params['created']['gte'] ?? PHP_INT_MIN;
        $lte = $params['created']['lte'] ?? PHP_INT_MAX;

        $all = array_values(array_filter($this->sessions[$account] ?? [], function (array $session) use ($params, $gte, $lte) {
            $intentId = is_array($session['payment_intent']) ? $session['payment_intent']['id'] : $session['payment_intent'];

            return $session['created'] >= $gte && $session['created'] <= $lte
                && (! isset($params['status']) || $session['status'] === $params['status'])
                && (! isset($params['payment_intent']) || $intentId === $params['payment_intent']);
        }));

        $offset = 0;

        if (isset($params['starting_after'])) {
            foreach ($all as $i => $session) {
                if ($session['id'] === $params['starting_after']) {
                    $offset = $i + 1;
                }
            }
        }

        $size = $this->pageSize ?? (int) ($params['limit'] ?? 10);
        $expand = $params['expand'] ?? [];

        $page = array_map(function (array $session) use ($expand) {
            $intent = $session['payment_intent'];

            if (! in_array('data.payment_intent', $expand, true) && ! in_array('data.payment_intent.latest_charge', $expand, true)) {
                $session['payment_intent'] = is_array($intent) ? $intent['id'] : $intent;
            } elseif (is_array($intent) && ! in_array('data.payment_intent.latest_charge', $expand, true) && is_array($intent['latest_charge'] ?? null)) {
                $session['payment_intent']['latest_charge'] = $intent['latest_charge']['id'];
            }

            return $session;
        }, array_slice($all, $offset, $size));

        return $this->json([
            'object' => 'list',
            'url' => '/v1/checkout/sessions',
            'data' => $page,
            'has_more' => $offset + $size < count($all),
        ]);
    }

    private function json(array $body): array
    {
        return [json_encode($body), 200, ['request-id' => 'req_fake']];
    }
}

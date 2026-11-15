<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Stripe\ApiRequestor;
use Tests\Support\FakeStripeHttpClient;

abstract class TestCase extends BaseTestCase
{
    protected function fakeStripe(): FakeStripeHttpClient
    {
        $fake = new FakeStripeHttpClient;

        ApiRequestor::setHttpClient($fake);

        $this->beforeApplicationDestroyed(fn () => ApiRequestor::setHttpClient(null));

        return $fake;
    }
}

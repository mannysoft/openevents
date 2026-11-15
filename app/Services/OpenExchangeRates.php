<?php

namespace App\Services;

use GuzzleHttp;

class OpenExchangeRates
{
    public function getCurrencies()
    {
        $client = new GuzzleHttp\Client();
        return $client->get(config('services.open_exchange_rates.api_url') . 'currencies.json?app_id=' . config('services.open_exchange_rates.app_id'));
    }

    public function getLatest()
    {
        $client = new GuzzleHttp\Client();
        return $client->get(config('services.open_exchange_rates.api_url') . 'latest.json?app_id=' . config('services.open_exchange_rates.app_id'));
    }
}

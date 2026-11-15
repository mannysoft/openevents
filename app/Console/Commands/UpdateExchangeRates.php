<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OpenExchangeRates;
use App\Models\Currency;

class UpdateExchangeRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update_exchange_rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to update exchange rates table in database.';

    /**
     * Execute the console command.s
     *
     * @return mixed
     */
    public function handle()
    {
        // $cost = Currency::getEquivalentRate('CAD', 10, ['GBP']);
        // dd($cost[0]['converted_rate']);
        $oer = new OpenExchangeRates();
        $response = $oer->getCurrencies();
        $currencies = json_decode($response->getBody(), true);
        foreach ($currencies as $code => $title) {
            $currency = Currency::firstOrNew([
                'code' => $code
            ]);
            $currency->title = $title;
            $currency->save();
        }
        $response = $oer->getLatest();
        $latest = json_decode($response->getBody(), true);
        foreach ($latest['rates'] as $code => $rate) {
            $currency = Currency::firstOrNew([
                'code' => $code
            ]);
            $currency->value = $rate;
            $currency->save();
            $this->info("Updated currency: {$code} with rate: {$rate}");
        }
    }
}

<?php

namespace App\Models;

/*
    Attendize.com   - Event Management & Ticketing
    */

use Illuminate\Support\Facades\DB;

/**
 * Description of Currency.
 *
 * @author Dave
 */
class Currency extends \Illuminate\Database\Eloquent\Model
{
    /**
     * Indicates whether the model should be timestamped.
     *
     * @var bool $timestamps
     */
    public $timestamps = false;
    /**
     * The database table used by the model.
     *
     * @var string $table
     */
    protected $table = 'currencies';
    /**
     * Indicates whether the model should use soft deletes.
     *
     * @var bool $softDelete
     */
    protected $softDelete = false;

    protected $fillable = ['code', 'title'];

    /**
     * The event associated with the currency.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function event()
    {
        return $this->belongsTo(\App\Models\Event::class);
    }

    public function scopeGetEquivalentRate($query, $baseCurrencyCode, $amount, $filterCurrencies)
    {
        $baseCurrency = $this->whereCode($baseCurrencyCode)->first();
        $currencyMultiplier = $amount / $baseCurrency->value;
        $query->whereIn('code', $filterCurrencies);
        return $query->get(["*", DB::raw("FORMAT((`value` * {$currencyMultiplier}), 2) AS `converted_rate`")]);
    }
}

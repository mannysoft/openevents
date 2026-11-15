<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DiscountCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'code',
        'type',
        'value',
        'quantity_available',
        'quantity_used',
        'min_quantity',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'value' => 'integer',
            'quantity_available' => 'integer',
            'quantity_used' => 'integer',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $code) {
            $code->code = Str::upper(trim($code->code));
        });

        static::updating(function (self $code) {
            $code->code = Str::upper(trim($code->code));
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isUsable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return $this->quantity_available === null
            || $this->quantity_used < $this->quantity_available;
    }

    public function discountFor(int $subtotalMinor): int
    {
        $discount = match ($this->type) {
            DiscountType::Percentage => (int) round($subtotalMinor * $this->value / 100),
            DiscountType::Fixed => $this->value,
        };

        return min($discount, $subtotalMinor);
    }
}

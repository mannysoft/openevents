<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'title',
        'description',
        'price',
        'quantity_available',
        'quantity_sold',
        'min_per_order',
        'max_per_order',
        'sales_start_at',
        'sales_end_at',
        'is_hidden',
        'sort_order',
        'legacy_id',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'quantity_available' => 'integer',
            'quantity_sold' => 'integer',
            'sales_start_at' => 'datetime',
            'sales_end_at' => 'datetime',
            'is_hidden' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function remaining(): ?int
    {
        if ($this->quantity_available === null) {
            return null;
        }

        return max(0, $this->quantity_available - $this->quantity_sold);
    }

    public function isSoldOut(): bool
    {
        return $this->remaining() === 0;
    }

    public function isOnSale(): bool
    {
        if ($this->is_hidden || $this->isSoldOut()) {
            return false;
        }

        $now = now();

        if ($this->sales_start_at && $now->lt($this->sales_start_at)) {
            return false;
        }

        if ($this->sales_end_at && $now->gt($this->sales_end_at)) {
            return false;
        }

        return true;
    }

    public function priceLabel(): string
    {
        return Money::format($this->price, $this->event->currency_code);
    }
}

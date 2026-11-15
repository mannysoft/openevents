<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'event_id',
        'organiser_id',
        'status',
        'first_name',
        'last_name',
        'email',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'currency_code',
        'discount_code_id',
        'stripe_payment_intent_id',
        'stripe_account_id',
        'application_fee_amount',
        'refunded_amount',
        'paid_at',
        'refunded_at',
        'cancelled_at',
        'legacy_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'tax_total' => 'integer',
            'total' => 'integer',
            'application_fee_amount' => 'integer',
            'refunded_amount' => 'integer',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function organiser(): BelongsTo
    {
        return $this->belongsTo(Organiser::class);
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(Attendee::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === OrderStatus::Completed;
    }

    public function isRefundable(): bool
    {
        return $this->isCompleted()
            && $this->refunded_amount < $this->total
            && $this->stripe_payment_intent_id !== null;
    }

    public function refundableMinor(): int
    {
        return max(0, $this->total - $this->refunded_amount);
    }

    public function name(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
    }

    public function totalLabel(): string
    {
        return Money::format($this->total, $this->currency_code);
    }

    public function netToOrganiserMinor(): int
    {
        return max(0, $this->total - $this->application_fee_amount);
    }
}

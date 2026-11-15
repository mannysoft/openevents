<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Organiser extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'email',
        'phone',
        'about',
        'logo_path',
        'currency_code',
        'timezone',
        'stripe_account_id',
        'stripe_charges_enabled',
        'stripe_details_submitted',
        'stripe_onboarded_at',
        'platform_fee_percent',
        'legacy_id',
    ];

    protected function casts(): array
    {
        return [
            'stripe_charges_enabled' => 'boolean',
            'stripe_details_submitted' => 'boolean',
            'stripe_onboarded_at' => 'datetime',
            'platform_fee_percent' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $organiser) {
            $organiser->slug ??= static::uniqueSlug($organiser->name);
        });
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'organiser';
        $slug = $base;
        $i = 2;

        while (static::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function feePercent(): float
    {
        return (float) ($this->platform_fee_percent ?? config('services.stripe.fee_percent', 5));
    }

    public function stripeReady(): bool
    {
        return $this->stripe_account_id !== null && $this->stripe_charges_enabled;
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? asset('storage/'.$this->logo_path) : null;
    }
}

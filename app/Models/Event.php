<?php

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organiser_id',
        'title',
        'slug',
        'description',
        'cover_image_path',
        'start_date',
        'end_date',
        'timezone',
        'venue_name',
        'address1',
        'city',
        'state',
        'postal_code',
        'country_code',
        'latitude',
        'longitude',
        'currency_code',
        'status',
        'is_public',
        'max_attendees',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'status' => EventStatus::class,
            'is_public' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $event) {
            $event->slug ??= static::uniqueSlug($event->organiser_id, $event->title);
        });
    }

    public static function uniqueSlug(int $organiserId, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'event';
        $slug = $base;
        $i = 2;

        while (static::withTrashed()
            ->where('organiser_id', $organiserId)
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

    public function organiser(): BelongsTo
    {
        return $this->belongsTo(Organiser::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class)->orderBy('sort_order');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(Attendee::class);
    }

    public function discountCodes(): HasMany
    {
        return $this->hasMany(DiscountCode::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort_order');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', EventStatus::Live)->where('is_public', true);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_date', '>=', now());
    }

    public function isLive(): bool
    {
        return $this->status === EventStatus::Live;
    }

    public function hasEnded(): bool
    {
        return $this->end_date !== null && $this->end_date->isPast();
    }

    public function visibleTickets(): HasMany
    {
        return $this->tickets()->where('is_hidden', false);
    }

    protected function minPrice(): Attribute
    {
        return Attribute::get(fn () => $this->tickets->min('price') ?? 0);
    }

    public function revenueMinor(): int
    {
        return (int) $this->orders()->where('status', 'completed')->sum('total');
    }

    public function ticketsSold(): int
    {
        return (int) $this->attendees()->where('is_cancelled', false)->count();
    }

    public function coverImageUrl(): ?string
    {
        return $this->cover_image_path ? asset('storage/'.$this->cover_image_path) : null;
    }

    public function url(): string
    {
        return route('events.show', $this);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoungeEventUniqueLink extends Model
{
    protected $connection = 'mysql_lounge';

    protected $table = 'event_unique_links';

    protected $fillable = ['event_id', 'token', 'created_by', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}

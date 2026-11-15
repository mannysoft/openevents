<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Reserved = 'reserved';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function isPaid(): bool
    {
        return $this === self::Completed;
    }
}

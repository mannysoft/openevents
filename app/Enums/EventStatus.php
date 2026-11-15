<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Live = 'live';
    case Ended = 'ended';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}

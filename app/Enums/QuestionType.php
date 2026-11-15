<?php

namespace App\Enums;

enum QuestionType: string
{
    case Text = 'text';
    case Select = 'select';
    case Checkbox = 'checkbox';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}

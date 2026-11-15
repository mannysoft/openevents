<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Event;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'title' => 'Dietary requirements',
            'type' => QuestionType::Text,
            'options' => null,
            'is_required' => false,
            'applies_to' => 'attendee',
            'sort_order' => 0,
        ];
    }
}

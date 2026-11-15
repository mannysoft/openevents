<?php

namespace App\Livewire\Admin\Questions;

use App\Enums\QuestionType;
use App\Models\Answer;
use App\Models\Event;
use App\Models\Question;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Registration questions')]
class Index extends Component
{
    public Event $event;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $type = QuestionType::Text->value;

    public string $options = '';

    public bool $is_required = false;

    public string $applies_to = 'attendee';

    public string $sort_order = '0';

    public function mount(Event $event): void
    {
        Gate::authorize('view', $event);

        $this->event = $event;
    }

    public function openCreate(): void
    {
        Gate::authorize('update', $this->event);

        $this->resetForm();
        $this->sort_order = (string) (((int) $this->questionQuery()->max('sort_order')) + 1);
        $this->showForm = true;
    }

    public function openEdit(int $questionId): void
    {
        Gate::authorize('update', $this->event);

        $question = $this->questionQuery()->findOrFail($questionId);

        $this->resetValidation();

        $this->editingId = $question->id;
        $this->title = $question->title;
        $this->type = $question->type->value;
        $this->options = implode(', ', $question->options ?? []);
        $this->is_required = $question->is_required;
        $this->applies_to = $question->applies_to;
        $this->sort_order = (string) $question->sort_order;
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        Gate::authorize('update', $this->event);

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(QuestionType::class)],
            'is_required' => ['boolean'],
            'applies_to' => ['required', Rule::in(['attendee', 'order'])],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $options = $this->optionList();

        if ($this->requiresOptions() && $options === []) {
            $this->addError('options', 'Add at least one option for a select or checkbox question.');

            return;
        }

        $attributes = [
            'title' => trim($this->title),
            'type' => $this->type,
            'options' => $this->requiresOptions() ? $options : null,
            'is_required' => $this->is_required,
            'applies_to' => $this->applies_to,
            'sort_order' => (int) $this->sort_order,
        ];

        if ($this->editingId !== null) {
            $this->questionQuery()->whereKey($this->editingId)->firstOrFail()->update($attributes);

            session()->flash('status', 'Question updated.');
        } else {
            Question::create(['event_id' => $this->event->id] + $attributes);

            session()->flash('status', 'Question created.');
        }

        $this->resetForm();
    }

    public function move(int $questionId, string $direction): void
    {
        Gate::authorize('update', $this->event);

        $ids = $this->questionQuery()->orderBy('sort_order')->orderBy('id')->pluck('id')->all();
        $position = array_search($questionId, $ids, true);

        if ($position === false) {
            return;
        }

        $target = $direction === 'up' ? $position - 1 : $position + 1;

        if (! array_key_exists($target, $ids)) {
            return;
        }

        [$ids[$position], $ids[$target]] = [$ids[$target], $ids[$position]];

        foreach ($ids as $index => $id) {
            $this->questionQuery()->whereKey($id)->update(['sort_order' => $index + 1]);
        }
    }

    public function delete(int $questionId): void
    {
        Gate::authorize('update', $this->event);

        $this->questionQuery()->whereKey($questionId)->delete();

        if ($this->editingId === $questionId) {
            $this->resetForm();
        }

        session()->flash('status', 'Question deleted.');
    }

    /**
     * The questions relation is ordered by sort_order, which cannot be carried
     * into UPDATE/DELETE statements on every database driver.
     *
     * @return Builder<Question>
     */
    protected function questionQuery()
    {
        return Question::query()->where('event_id', $this->event->id);
    }

    /**
     * @return array<int, string>
     */
    protected function optionList(): array
    {
        return collect(explode(',', $this->options))
            ->map(fn (string $option): string => trim($option))
            ->filter(fn (string $option): bool => $option !== '')
            ->unique()
            ->values()
            ->all();
    }

    protected function requiresOptions(): bool
    {
        return in_array($this->type, [QuestionType::Select->value, QuestionType::Checkbox->value], true);
    }

    protected function resetForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->title = '';
        $this->type = QuestionType::Text->value;
        $this->options = '';
        $this->is_required = false;
        $this->applies_to = 'attendee';
        $this->sort_order = '0';

        $this->resetValidation();
    }

    public function render()
    {
        // Question does not define an answers() relation yet, so count answers with a
        // correlated subquery until one exists.
        $questions = method_exists(Question::class, 'answers')
            ? $this->questionQuery()->withCount('answers')->orderBy('sort_order')->get()
            : $this->questionQuery()->orderBy('sort_order')->addSelect([
                'answers_count' => Answer::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('answers.question_id', 'questions.id'),
            ])->get();

        return view('livewire.admin.questions.index', [
            'questions' => $questions,
            'types' => QuestionType::cases(),
        ]);
    }
}

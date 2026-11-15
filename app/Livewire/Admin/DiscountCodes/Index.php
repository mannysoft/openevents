<?php

namespace App\Livewire\Admin\DiscountCodes;

use App\Enums\DiscountType;
use App\Models\DiscountCode;
use App\Models\Event;
use App\Support\Money;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Discount codes')]
class Index extends Component
{
    public Event $event;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $code = '';

    public string $type = DiscountType::Percentage->value;

    public string $value = '';

    public ?string $quantity_available = null;

    public string $min_quantity = '1';

    public ?string $expires_at = null;

    public bool $is_active = true;

    public function mount(Event $event): void
    {
        Gate::authorize('view', $event);

        $this->event = $event;
    }

    public function openCreate(): void
    {
        Gate::authorize('update', $this->event);

        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $discountCodeId): void
    {
        Gate::authorize('update', $this->event);

        $discountCode = $this->event->discountCodes()->findOrFail($discountCodeId);

        $this->resetValidation();

        $this->editingId = $discountCode->id;
        $this->code = $discountCode->code;
        $this->type = $discountCode->type->value;
        $this->value = $discountCode->type === DiscountType::Percentage
            ? (string) $discountCode->value
            : (string) Money::toMajor($discountCode->value, $this->event->currency_code);
        $this->quantity_available = $discountCode->quantity_available !== null
            ? (string) $discountCode->quantity_available
            : null;
        $this->min_quantity = (string) $discountCode->min_quantity;
        $this->expires_at = $discountCode->expires_at?->format('Y-m-d\TH:i');
        $this->is_active = $discountCode->is_active;
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        Gate::authorize('update', $this->event);

        $this->code = Str::upper(trim($this->code));

        if ($this->quantity_available !== null && trim($this->quantity_available) === '') {
            $this->quantity_available = null;
        }

        $quantityUsed = $this->editingId !== null
            ? (int) $this->event->discountCodes()->whereKey($this->editingId)->value('quantity_used')
            : 0;

        $this->validate([
            'code' => [
                'required',
                'string',
                'max:64',
                Rule::unique('discount_codes', 'code')
                    ->where(fn ($query) => $query->where('event_id', $this->event->id))
                    ->ignore($this->editingId),
            ],
            'type' => ['required', Rule::enum(DiscountType::class)],
            'value' => $this->valueRules(),
            'quantity_available' => [
                'nullable',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, Closure $fail) use ($quantityUsed): void {
                    if ($value !== null && $value !== '' && (int) $value < $quantityUsed) {
                        $fail('The available quantity cannot be less than the '.$quantityUsed.' already used.');
                    }
                },
            ],
            'min_quantity' => ['required', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ]);

        $attributes = [
            'code' => $this->code,
            'type' => $this->type,
            'value' => $this->type === DiscountType::Percentage->value
                ? (int) $this->value
                : Money::toMinor($this->value, $this->event->currency_code),
            'quantity_available' => $this->quantity_available !== null && $this->quantity_available !== ''
                ? (int) $this->quantity_available
                : null,
            'min_quantity' => (int) $this->min_quantity,
            'expires_at' => $this->expires_at ? Carbon::parse($this->expires_at) : null,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId !== null) {
            $this->event->discountCodes()->whereKey($this->editingId)->firstOrFail()->update($attributes);

            session()->flash('status', 'Discount code updated.');
        } else {
            $this->event->discountCodes()->create($attributes);

            session()->flash('status', 'Discount code created.');
        }

        $this->resetForm();
    }

    public function toggleActive(int $discountCodeId): void
    {
        Gate::authorize('update', $this->event);

        $discountCode = $this->event->discountCodes()->findOrFail($discountCodeId);
        $discountCode->update(['is_active' => ! $discountCode->is_active]);

        session()->flash('status', $discountCode->is_active
            ? 'Discount code activated.'
            : 'Discount code deactivated.');
    }

    public function delete(int $discountCodeId): void
    {
        Gate::authorize('update', $this->event);

        $this->event->discountCodes()->whereKey($discountCodeId)->delete();

        if ($this->editingId === $discountCodeId) {
            $this->resetForm();
        }

        session()->flash('status', 'Discount code deleted.');
    }

    public function formatValue(DiscountCode $discountCode): string
    {
        return $discountCode->type === DiscountType::Percentage
            ? $discountCode->value.'%'
            : Money::format($discountCode->value, $this->event->currency_code);
    }

    /**
     * @return array<int, mixed>
     */
    protected function valueRules(): array
    {
        if ($this->type === DiscountType::Percentage->value) {
            return ['required', 'integer', 'between:1,100'];
        }

        return [
            'required',
            'numeric',
            'gt:0',
            function (string $attribute, mixed $value, Closure $fail): void {
                if (Money::toMinor($value, $this->event->currency_code) < 1) {
                    $fail('The discount must be at least '.Money::format(1, $this->event->currency_code).'.');
                }
            },
        ];
    }

    protected function resetForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->code = '';
        $this->type = DiscountType::Percentage->value;
        $this->value = '';
        $this->quantity_available = null;
        $this->min_quantity = '1';
        $this->expires_at = null;
        $this->is_active = true;

        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.discounts.index', [
            'discountCodes' => $this->event->discountCodes()->orderBy('code')->get(),
            'types' => DiscountType::cases(),
        ]);
    }
}

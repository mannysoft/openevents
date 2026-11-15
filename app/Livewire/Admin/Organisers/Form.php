<?php

namespace App\Livewire\Admin\Organisers;

use App\Models\Organiser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class Form extends Component
{
    use WithFileUploads;

    public ?Organiser $organiser = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $about = '';

    public $logo = null;

    public string $currency_code = '';

    public string $timezone = '';

    public string $platform_fee_percent = '';

    public function mount(?Organiser $organiser = null): void
    {
        if ($organiser?->exists) {
            Gate::authorize('update', $organiser);

            $this->organiser = $organiser;
            $this->name = $organiser->name;
            $this->email = $organiser->email;
            $this->phone = (string) $organiser->phone;
            $this->about = (string) $organiser->about;
            $this->currency_code = $organiser->currency_code;
            $this->timezone = $organiser->timezone;
            $this->platform_fee_percent = $organiser->platform_fee_percent !== null
                ? (string) $organiser->platform_fee_percent
                : '';

            return;
        }

        $this->currency_code = config('events.default_currency');
        $this->timezone = config('events.default_timezone');
        $this->email = (string) auth()->user()->email;
    }

    public function save()
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'about' => ['nullable', 'string', 'max:5000'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'currency_code' => ['required', Rule::in(array_keys(config('events.currencies')))],
            'timezone' => ['required', 'timezone'],
            'platform_fee_percent' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        $attributes = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone !== '' ? $this->phone : null,
            'about' => $this->about !== '' ? $this->about : null,
            'currency_code' => $this->currency_code,
            'timezone' => $this->timezone,
            'platform_fee_percent' => $this->platform_fee_percent !== '' ? $this->platform_fee_percent : null,
        ];

        if ($this->logo) {
            $attributes['logo_path'] = $this->logo->store('organisers', 'public');

            if ($this->organiser?->logo_path) {
                Storage::disk('public')->delete($this->organiser->logo_path);
            }
        }

        if ($this->organiser?->exists) {
            $this->organiser->update($attributes);
            $status = 'Organiser updated.';
        } else {
            $this->organiser = Organiser::create($attributes + ['user_id' => auth()->id()]);
            $status = 'Organiser created.';
        }

        session()->flash('status', $status);

        return $this->redirect(route('admin.events.index', $this->organiser), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.organisers.form')
            ->title($this->organiser?->exists ? 'Edit '.$this->organiser->name : 'New organiser');
    }
}

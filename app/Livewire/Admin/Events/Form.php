<?php

namespace App\Livewire\Admin\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organiser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class Form extends Component
{
    use WithFileUploads;

    public Organiser $organiser;

    public ?Event $event = null;

    public string $title = '';

    public string $description = '';

    public string $start_date = '';

    public string $end_date = '';

    public string $timezone = '';

    public string $venue_name = '';

    public string $address1 = '';

    public string $city = '';

    public string $state = '';

    public string $postal_code = '';

    public string $country_code = 'GB';

    public $cover_image = null;

    public bool $is_public = true;

    public string $max_attendees = '';

    public function mount(Organiser $organiser, ?Event $event = null): void
    {
        $this->organiser = $organiser;

        if ($event?->exists) {
            abort_unless($event->organiser_id === $organiser->id, 404);

            Gate::authorize('update', $event);

            $this->event = $event;
            $this->title = $event->title;
            $this->description = (string) $event->description;
            $this->timezone = $event->timezone;
            $this->start_date = $event->start_date
                ? $event->start_date->copy()->setTimezone($event->timezone)->format('Y-m-d\TH:i')
                : '';
            $this->end_date = $event->end_date
                ? $event->end_date->copy()->setTimezone($event->timezone)->format('Y-m-d\TH:i')
                : '';
            $this->venue_name = (string) $event->venue_name;
            $this->address1 = (string) $event->address1;
            $this->city = (string) $event->city;
            $this->state = (string) $event->state;
            $this->postal_code = (string) $event->postal_code;
            $this->country_code = (string) ($event->country_code ?: 'GB');
            $this->is_public = (bool) $event->is_public;
            $this->max_attendees = $event->max_attendees !== null ? (string) $event->max_attendees : '';

            return;
        }

        Gate::authorize('update', $organiser);

        $this->timezone = $organiser->timezone;
    }

    public function save()
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'timezone' => ['required', 'timezone'],
            'venue_name' => ['nullable', 'string', 'max:255'],
            'address1' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'is_public' => ['boolean'],
            'max_attendees' => ['nullable', 'integer', 'min:1'],
        ]);

        $attributes = [
            'title' => $this->title,
            'description' => $this->description !== '' ? $this->description : null,
            'start_date' => Carbon::parse($this->start_date, $this->timezone)->utc(),
            'end_date' => $this->end_date !== '' ? Carbon::parse($this->end_date, $this->timezone)->utc() : null,
            'timezone' => $this->timezone,
            'venue_name' => $this->venue_name !== '' ? $this->venue_name : null,
            'address1' => $this->address1 !== '' ? $this->address1 : null,
            'city' => $this->city !== '' ? $this->city : null,
            'state' => $this->state !== '' ? $this->state : null,
            'postal_code' => $this->postal_code !== '' ? $this->postal_code : null,
            'country_code' => $this->country_code !== '' ? strtoupper($this->country_code) : null,
            'is_public' => $this->is_public,
            'max_attendees' => $this->max_attendees !== '' ? (int) $this->max_attendees : null,
        ];

        if ($this->cover_image) {
            $attributes['cover_image_path'] = $this->cover_image->store('events', 'public');

            if ($this->event?->cover_image_path) {
                Storage::disk('public')->delete($this->event->cover_image_path);
            }
        }

        if ($this->event?->exists) {
            $this->event->update($attributes);

            session()->flash('status', 'Event saved.');

            return $this->redirect(route('admin.events.edit', [$this->organiser, $this->event]), navigate: true);
        }

        $this->event = $this->organiser->events()->create($attributes + [
            'currency_code' => $this->organiser->currency_code,
            'status' => EventStatus::Draft,
        ]);

        session()->flash('status', 'Event created.');

        return $this->redirect(route('admin.events.edit', [$this->organiser, $this->event]), navigate: true);
    }

    public function publish()
    {
        if (! $this->event?->exists) {
            return null;
        }

        Gate::authorize('publish', $this->event);

        $this->event->update(['status' => EventStatus::Live]);

        session()->flash('status', $this->organiser->stripeReady()
            ? 'Event published.'
            : 'Event published. Payments settle in the platform Stripe account until this organiser connects Stripe.');

        return $this->redirect(route('admin.events.edit', [$this->organiser, $this->event]), navigate: true);
    }

    public function unpublish()
    {
        if (! $this->event?->exists) {
            return null;
        }

        Gate::authorize('update', $this->event);

        $this->event->update(['status' => EventStatus::Draft]);
        session()->flash('status', 'Event unpublished and back in draft.');

        return $this->redirect(route('admin.events.edit', [$this->organiser, $this->event]), navigate: true);
    }

    public function delete()
    {
        if (! $this->event?->exists) {
            return null;
        }

        Gate::authorize('delete', $this->event);

        $this->event->delete();

        session()->flash('status', 'Event deleted.');

        return $this->redirect(route('admin.events.index', $this->organiser), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.events.form')
            ->title($this->event?->exists ? 'Edit '.$this->event->title : 'New event');
    }
}

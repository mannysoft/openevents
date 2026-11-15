<?php

namespace App\Livewire;

use Livewire\Component;

class FriendDetails extends Component
{
    public string $firstName = '';
    public string $lastName  = '';
    public string $email     = '';
    public string $phone     = '';

    /** Whether we are showing the read-only card or the editable form. */
    public bool $saved   = false;
    public bool $editing = false;

    public function mount(): void
    {
        $details = session('bring_friend_details');

        if ($details) {
            $this->firstName = $details['first_name'];
            $this->lastName  = $details['last_name'];
            $this->email     = $details['email'];
            $this->phone     = $details['phone'] ?? '';
            $this->saved     = true;
        }
    }

    protected function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName'  => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255'],
            'phone'     => ['nullable', 'string', 'max:50'],
        ];
    }

    protected function messages(): array
    {
        return [
            'firstName.required' => "Your friend's first name is required.",
            'lastName.required'  => "Your friend's last name is required.",
            'email.required'     => "Your friend's email address is required.",
            'email.email'        => "Please enter a valid email address for your friend.",
        ];
    }

    public function save(): void
    {
        $this->validate();

        session()->put('bring_friend_details', [
            'first_name' => $this->firstName,
            'last_name'  => $this->lastName,
            'email'      => $this->email,
            'phone'      => $this->phone,
        ]);

        $this->saved   = true;
        $this->editing = false;

        $this->dispatch('friend-details-saved');
    }

    public function edit(): void
    {
        $this->editing = true;
        $this->saved   = false;
    }

    public function remove(): void
    {
        session()->forget('bring_friend_details');

        $this->firstName = '';
        $this->lastName  = '';
        $this->email     = '';
        $this->phone     = '';
        $this->saved     = false;
        $this->editing   = false;

        $this->dispatch('friend-details-removed');
    }

    public function render()
    {
        return view('livewire.friend-details');
    }
}

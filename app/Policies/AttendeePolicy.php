<?php

namespace App\Policies;

use App\Models\Attendee;
use App\Models\User;

class AttendeePolicy
{
    public function view(User $user, Attendee $attendee): bool
    {
        return $attendee->event->organiser->user_id === $user->id;
    }

    public function update(User $user, Attendee $attendee): bool
    {
        return $attendee->event->organiser->user_id === $user->id;
    }
}

<?php

namespace App\Policies;

use App\Models\Organiser;
use App\Models\User;

class OrganiserPolicy
{
    public function view(User $user, Organiser $organiser): bool
    {
        return $organiser->user_id === $user->id;
    }

    public function update(User $user, Organiser $organiser): bool
    {
        return $organiser->user_id === $user->id;
    }

    public function delete(User $user, Organiser $organiser): bool
    {
        return $organiser->user_id === $user->id;
    }

    public function manageStripe(User $user, Organiser $organiser): bool
    {
        return $organiser->user_id === $user->id;
    }
}

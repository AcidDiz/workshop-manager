<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workshop;

class WorkshopPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('workshops.view');
    }

    public function view(User $user, Workshop $workshop): bool
    {
        return $user->can('workshops.view');
    }

    public function create(User $user): bool
    {
        return $user->can('workshops.manage');
    }

    public function update(User $user, Workshop $workshop): bool
    {
        return $user->can('workshops.manage');
    }

    public function delete(User $user, Workshop $workshop): bool
    {
        return $user->can('workshops.manage');
    }

    public function restore(User $user, Workshop $workshop): bool
    {
        return $user->can('workshops.manage');
    }

    public function forceDelete(User $user, Workshop $workshop): bool
    {
        return $user->can('workshops.manage');
    }

    public function attachRegistration(User $user, Workshop $workshop): bool
    {
        return $this->view($user, $workshop);
    }

    public function detachRegistration(User $user, Workshop $workshop): bool
    {
        return $this->view($user, $workshop);
    }
}

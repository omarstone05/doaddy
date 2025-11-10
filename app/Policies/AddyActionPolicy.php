<?php

namespace App\Policies;

use App\Models\AddyAction;
use App\Models\User;

class AddyActionPolicy
{
    public function confirm(User $user, AddyAction $action): bool
    {
        return $user->id === $action->user_id 
            && $action->status === 'pending';
    }

    public function cancel(User $user, AddyAction $action): bool
    {
        return $user->id === $action->user_id 
            && in_array($action->status, ['pending', 'confirmed']);
    }

    public function rate(User $user, AddyAction $action): bool
    {
        return $user->id === $action->user_id 
            && $action->status === 'executed';
    }
}


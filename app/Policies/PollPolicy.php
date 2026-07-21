<?php

namespace App\Policies;

use App\Models\Poll;
use App\Models\User;

class PollPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view polls');
    }

    public function view(User $user, Poll $poll): bool
    {
        return $user->can('view polls');
    }

    public function create(User $user): bool
    {
        return $user->can('create polls');
    }

    public function update(User $user, Poll $poll): bool
    {
        return $user->id === $poll->user_id && $user->can('edit polls');
    }

    public function delete(User $user, Poll $poll): bool
    {
        return $user->id === $poll->user_id || $user->can('delete polls');
    }

    public function vote(User $user, Poll $poll): bool
    {
        if (! $user->can('vote polls')) {
            return false;
        }

        if ($poll->isExpired()) {
            return false;
        }

        return true;
    }

    public function exportResults(User $user, Poll $poll): bool
    {
        return $user->id === $poll->user_id || $user->can('export poll results');
    }
}

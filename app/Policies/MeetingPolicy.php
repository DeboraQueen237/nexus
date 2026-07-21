<?php

namespace App\Policies;

use App\Models\Meeting;
use App\Models\User;

class MeetingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view meetings');
    }

    public function view(User $user, Meeting $meeting): bool
    {
        if (! $user->can('view meetings')) {
            return false;
        }

        return $user->id === $meeting->user_id
            || $meeting->participants()->where('user_id', $user->id)->exists()
            || $meeting->allow_link_join
            || $user->can('manage meetings');
    }

    public function create(User $user): bool
    {
        return $user->can('create meetings');
    }

    public function update(User $user, Meeting $meeting): bool
    {
        return $user->id === $meeting->user_id || $user->can('manage meetings');
    }

    public function delete(User $user, Meeting $meeting): bool
    {
        return $user->id === $meeting->user_id || $user->can('manage meetings') || $user->can('delete meetings');
    }

    public function join(User $user, Meeting $meeting): bool
    {
        if (! $user->can('join meetings')) {
            return false;
        }

        if ($meeting->isEnded() || $meeting->status === 'cancelled') {
            return false;
        }

        // Organisateur, participant déjà invité, ou n'importe quel
        // utilisateur authentifié de la plateforme si le lien
        // d'invitation est autorisé (comme Zoom/Meet).
        return $user->id === $meeting->user_id
            || $meeting->participants()->where('user_id', $user->id)->exists()
            || $meeting->allow_link_join;
    }

    public function record(User $user, Meeting $meeting): bool
    {
        return $user->id === $meeting->user_id || $user->can('record meetings');
    }
}

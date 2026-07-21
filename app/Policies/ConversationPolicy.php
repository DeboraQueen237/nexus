<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view messages');
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->participants()->where('user_id', $user->id)->exists();
    }

    public function send(User $user, Conversation $conversation): bool
    {
        return $user->can('send messages') && $this->view($user, $conversation);
    }

    public function createGroup(User $user): bool
    {
        return $user->can('create groups');
    }

    public function manageGroup(User $user, Conversation $conversation): bool
    {
        $pivot = $conversation->participants()->where('user_id', $user->id)->first()?->pivot;

        return ($pivot && $pivot->role === 'admin') || $user->can('manage groups');
    }

    public function deleteMessage(User $user, Conversation $conversation, int $authorId): bool
    {
        return $user->id === $authorId || $user->can('delete messages') || $user->can('moderate messages');
    }
}

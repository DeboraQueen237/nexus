<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Ici, on définit qui a le droit d'écouter quel canal privé. Reverb
| interroge cette route (POST /broadcasting/auth) avant d'autoriser un
| client à rejoindre un canal privé/présence.
|
*/

// Canal d'une conversation : uniquement ses participants.
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (! $conversation) {
        return false;
    }

    return $conversation->hasParticipant($user->id);
});

// Canal de présence : liste des utilisateurs en train de "regarder" une
// conversation (utile pour l'indicateur de frappe et le statut en ligne).
Broadcast::channel('presence-conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (! $conversation || ! $conversation->hasParticipant($user->id)) {
        return null;
    }

    return ['id' => $user->id, 'name' => $user->name];
});

// Canal personnel : notifications individuelles (nouveau message dans une
// conversation qu'on ne regarde pas, invitation à une réunion, etc.).
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Canal de présence d'une réunion WebRTC : sert à savoir qui est dans la
// salle (pour établir les connexions peer-to-peer) et de relais de
// signalisation (offres/réponses SDP, ICE candidates) via whisper().
Broadcast::channel('presence-meeting.{meetingId}', function ($user, $meetingId) {
    $meeting = \App\Models\Meeting::find($meetingId);

    if (! $meeting || ! Gate::forUser($user)->allows('join', $meeting)) {
        return null;
    }

    return ['id' => $user->id, 'name' => $user->name];
});

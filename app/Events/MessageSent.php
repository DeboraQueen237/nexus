<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        $this->message->loadMissing('user');

        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'content' => $this->message->content,
            'type' => $this->message->type,
            'parent_id' => $this->message->parent_id,
            'created_at' => $this->message->created_at->toIso8601String(),
            'created_at_human' => $this->message->created_at->format('H:i'),
            'attachment' => $this->message->attachment_path ? [
                'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($this->message->attachment_path),
                'name' => $this->message->attachment_name,
                'mime' => $this->message->attachment_mime,
                'size' => $this->message->attachment_size,
                'is_image' => str_starts_with((string) $this->message->attachment_mime, 'image/'),
            ] : null,
            'reactions' => [],
            'user' => [
                'id' => $this->message->user->id,
                'name' => $this->message->user->name,
                'initial' => mb_strtoupper(mb_substr($this->message->user->name, 0, 1)),
            ],
        ];
    }
}

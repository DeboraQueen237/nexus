<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'content',
        'type',
        'is_read',
        'parent_id',
        'expires_at',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'expires_at' => 'datetime',
        'attachment_size' => 'integer',
    ];

    // Relations
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Message::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Message::class, 'parent_id');
    }

    public function reactions()
    {
        return $this->hasMany(MessageReaction::class);
    }

    /**
     * Regroupe les réactions par emoji : ['👍' => ['count' => 2, 'mine' => true], ...]
     */
    public function reactionSummary(?int $currentUserId = null): array
    {
        $summary = [];

        foreach ($this->reactions as $reaction) {
            $summary[$reaction->emoji] ??= ['emoji' => $reaction->emoji, 'count' => 0, 'mine' => false];
            $summary[$reaction->emoji]['count']++;
            if ($currentUserId && $reaction->user_id === $currentUserId) {
                $summary[$reaction->emoji]['mine'] = true;
            }
        }

        return array_values($summary);
    }

    // Vérifier si le message est expiré
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
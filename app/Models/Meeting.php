<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'meeting_url',
        'invite_token',
        'allow_link_join',
        'platform',
        'status',
        'max_participants',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'allow_link_join' => 'boolean',
    ];

    // Relations
    public function organizer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'meeting_participants')
                    ->withPivot('status', 'joined_at')
                    ->withTimestamps();
    }

    // Vérifier si la réunion est en cours
    public function isOngoing(): bool
    {
        return $this->status === 'ongoing';
    }

    // Vérifier si la réunion est terminée
    public function isEnded(): bool
    {
        return $this->status === 'ended' || ($this->end_time && $this->end_time->isPast());
    }

    public function hasParticipant(int $userId): bool
    {
        return $this->user_id === $userId || $this->participants()->where('user_id', $userId)->exists();
    }

    public function inviteUrl(): string
    {
        return route('meetings.join-by-link', $this->invite_token);
    }
}
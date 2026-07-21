<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // <-- AJOUTER CETTE LIGNE

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles; // <-- AJOUTER HasRoles

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_confirmed_at);
    }

    public function loginHistories()
    {
        return $this->hasMany(LoginHistory::class)->latest('created_at');
    }

    // === RELATIONS EXISTANTES ===
    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
                    ->withPivot('last_read_at', 'role')
                    ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function polls()
    {
        return $this->hasMany(Poll::class);
    }

    public function pollVotes()
    {
        return $this->hasMany(PollVote::class);
    }

    public function articles()
    {
        return $this->hasMany(KbArticle::class);
    }

    public function favoriteArticles()
    {
        return $this->belongsToMany(KbArticle::class, 'kb_article_favorites')->withTimestamps();
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }

    public function meetingsAsParticipant()
    {
        return $this->belongsToMany(Meeting::class, 'meeting_participants')
                    ->withPivot('status', 'joined_at')
                    ->withTimestamps();
    }
}
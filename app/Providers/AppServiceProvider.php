<?php

namespace App\Providers;

use App\Models\Conversation;
use App\Models\KbArticle;
use App\Models\Meeting;
use App\Models\Poll;
use App\Policies\ConversationPolicy;
use App\Policies\KbArticlePolicy;
use App\Policies\MeetingPolicy;
use App\Policies\PollPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Association manuelle des policies : les noms de modèles (KbArticle,
     * Poll, Meeting, Conversation) ne suivent pas la convention Laravel
     * "Model" -> "ModelPolicy", donc la découverte automatique ne suffit pas.
     */
    protected $policies = [
        KbArticle::class => KbArticlePolicy::class,
        Poll::class => PollPolicy::class,
        Meeting::class => MeetingPolicy::class,
        Conversation::class => ConversationPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Le Super Admin outrepasse systématiquement toutes les vérifications
        // Gate/Policy — pas besoin de lui donner explicitement chaque permission.
        Gate::before(function ($user, string $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });

        // Politique de mot de passe renforcée, appliquée partout où
        // Password::defaults() est utilisé (inscription, reset, changement).
        Password::defaults(function () {
            $rule = Password::min(8)->letters()->mixedCase()->numbers()->symbols();

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });
    }
}

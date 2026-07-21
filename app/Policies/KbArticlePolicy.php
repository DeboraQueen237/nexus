<?php

namespace App\Policies;

use App\Models\KbArticle;
use App\Models\User;

class KbArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view articles');
    }

    public function view(User $user, KbArticle $article): bool
    {
        if ($article->status === 'published') {
            return $user->can('view articles');
        }

        // Brouillon : réservé à l'auteur ou à ceux qui peuvent voir les brouillons
        return $user->id === $article->user_id || $user->can('view draft articles');
    }

    public function create(User $user): bool
    {
        return $user->can('create articles');
    }

    public function update(User $user, KbArticle $article): bool
    {
        if ($user->can('edit all articles')) {
            return true;
        }

        return $user->id === $article->user_id && $user->can('edit articles');
    }

    public function delete(User $user, KbArticle $article): bool
    {
        return $user->id === $article->user_id || $user->can('delete articles');
    }

    public function publish(User $user, KbArticle $article): bool
    {
        return $user->can('publish articles');
    }

    public function manageCategories(User $user): bool
    {
        return $user->can('manage categories');
    }

    public function moderateComments(User $user): bool
    {
        return $user->can('moderate comments');
    }

    public function export(User $user): bool
    {
        return $user->can('export documentation');
    }
}

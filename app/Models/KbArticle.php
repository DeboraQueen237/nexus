<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class KbArticle extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'category_id',
        'user_id',
        'title',
        'slug',
        'content',
        'status',
        'views',
        'is_featured',
        'published_at',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
    ];

    // Slug automatique
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    // Relations
    public function category()
    {
        return $this->belongsTo(KbCategory::class, 'category_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'kb_article_favorites')->withTimestamps();
    }

    public function isFavoritedBy(int $userId): bool
    {
        return $this->favoritedBy()->where('user_id', $userId)->exists();
    }

    // Incrémenter les vues
    public function incrementViews(): void
    {
        $this->increment('views');
    }
}
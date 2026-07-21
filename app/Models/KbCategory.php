<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class KbCategory extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'lft',
        'rgt',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Slug automatique
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    // Relations
    public function parent()
    {
        return $this->belongsTo(KbCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(KbCategory::class, 'parent_id');
    }

    public function articles()
    {
        return $this->hasMany(KbArticle::class, 'category_id');
    }

    // Vérifier si c'est une catégorie racine
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Recalcule lft/rgt pour TOUTE l'arborescence (nested set). Appelée
     * après chaque création/déplacement/suppression de catégorie.
     * Simple et sûr (O(n) sur un nombre de catégories forcément restreint
     * pour une base de connaissances) plutôt qu'une mise à jour partielle
     * du nested set, plus rapide mais bien plus risquée à maintenir sans
     * tests automatisés.
     */
    public static function rebuildTree(): void
    {
        $counter = 1;

        static::whereNull('parent_id')->orderBy('name')->get()->each(function (self $root) use (&$counter) {
            $counter = static::assignBounds($root, $counter);
        });
    }

    protected static function assignBounds(self $node, int $left): int
    {
        $right = $left + 1;

        static::where('parent_id', $node->id)->orderBy('name')->get()->each(function (self $child) use (&$right) {
            $right = static::assignBounds($child, $right);
        });

        $node->forceFill(['lft' => $left, 'rgt' => $right])->saveQuietly();

        return $right + 1;
    }

    /**
     * Tous les identifiants de la branche (la catégorie + tous ses
     * descendants), calculés via parent_id de façon récursive — utilisé
     * pour afficher "tous les articles de cette catégorie et ses
     * sous-catégories".
     */
    public function descendantIds(): array
    {
        $ids = [$this->id];

        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->descendantIds());
        }

        return $ids;
    }
}
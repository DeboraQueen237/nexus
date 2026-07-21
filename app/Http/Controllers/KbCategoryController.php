<?php

namespace App\Http\Controllers;

use App\Models\KbCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KbCategoryController extends Controller
{
    public function index(): View
    {
        $categories = KbCategory::withCount('articles')
            ->with('parent')
            ->orderBy('name')
            ->get();

        return view('knowledge.categories.index', [
            'categories' => $categories,
            'tree' => $this->buildSelectTree($categories),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:kb_categories,id'],
            'is_active' => ['boolean'],
        ]);

        DB::transaction(function () use ($data, $request) {
            KbCategory::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'parent_id' => $data['parent_id'] ?? null,
                'is_active' => $request->boolean('is_active', true),
                // Placeholder, immédiatement recalculé par rebuildTree().
                'lft' => 0,
                'rgt' => 0,
            ]);

            KbCategory::rebuildTree();
        });

        return back()->with('success', 'Catégorie créée.');
    }

    public function update(Request $request, KbCategory $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:kb_categories,id'],
            'is_active' => ['boolean'],
        ]);

        if (isset($data['parent_id']) && (int) $data['parent_id'] === $category->id) {
            return back()->with('error', "Une catégorie ne peut pas être sa propre catégorie parente.");
        }

        // Empêche de déplacer une catégorie sous l'un de ses propres
        // descendants (créerait une boucle infinie dans l'arbre).
        if (! empty($data['parent_id']) && in_array((int) $data['parent_id'], $category->descendantIds(), true)) {
            return back()->with('error', "Impossible de déplacer une catégorie sous l'une de ses sous-catégories.");
        }

        DB::transaction(function () use ($category, $data, $request) {
            $category->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'parent_id' => $data['parent_id'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            KbCategory::rebuildTree();
        });

        return back()->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(KbCategory $category): RedirectResponse
    {
        if ($category->children()->exists()) {
            return back()->with('error', "Supprime d'abord les sous-catégories.");
        }

        if ($category->articles()->exists()) {
            return back()->with('error', "Cette catégorie contient encore des articles. Déplace-les avant de la supprimer.");
        }

        $category->delete();
        KbCategory::rebuildTree();

        return back()->with('success', 'Catégorie supprimée.');
    }

    /**
     * Construit un tableau plat [id => "— — Nom"] indenté selon la
     * profondeur, pratique pour un <select> de catégorie parente.
     */
    protected function buildSelectTree($categories, $parentId = null, $depth = 0): array
    {
        $result = [];

        foreach ($categories->where('parent_id', $parentId) as $category) {
            $result[$category->id] = str_repeat('— ', $depth) . $category->name;
            $result += $this->buildSelectTree($categories, $category->id, $depth + 1);
        }

        return $result;
    }
}

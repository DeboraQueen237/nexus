<?php

namespace App\Http\Controllers;

use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Services\MarkdownService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class KbArticleController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', KbArticle::class);

        $user = $request->user();
        $tab = $request->get('tab', 'published');

        $query = KbArticle::query()->with(['author', 'category']);

        if ($tab === 'mine') {
            $query->where('user_id', $user->id);
        } elseif ($tab === 'favorites') {
            $query->whereHas('favoritedBy', fn ($q) => $q->where('user_id', $user->id));
        } elseif ($tab === 'pending' && $user->can('publish articles')) {
            $query->where('status', 'pending');
        } else {
            $query->where('status', 'published');
        }

        if ($request->filled('q')) {
            $term = "%{$request->get('q')}%";
            $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('content', 'like', $term));
        }

        if ($request->filled('category')) {
            $category = KbCategory::where('slug', $request->get('category'))->first();
            if ($category) {
                $query->whereIn('category_id', $category->descendantIds());
            }
        }

        $articles = $query->latest('published_at')->latest()->paginate(12)->withQueryString();

        $categories = KbCategory::where('is_active', true)->withCount('articles')->orderBy('lft')->get();

        return view('knowledge.index', [
            'articles' => $articles,
            'categories' => $this->nestCategories($categories),
            'tab' => $tab,
            'canReview' => $user->can('publish articles'),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', KbArticle::class);

        $categories = KbCategory::where('is_active', true)->orderBy('lft')->get();

        return view('knowledge.create', [
            'categoryOptions' => $this->flatCategoryOptions($categories),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', KbArticle::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:kb_categories,id'],
            'content' => ['required', 'string'],
            'is_featured' => ['boolean'],
            'action' => ['required', 'in:draft,submit,publish'],
        ]);

        $status = 'draft';
        if ($data['action'] === 'submit') {
            $status = 'pending';
        } elseif ($data['action'] === 'publish' && $request->user()->can('publish articles')) {
            $status = 'published';
        }

        $article = KbArticle::create([
            'category_id' => $data['category_id'],
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'content' => $data['content'],
            'status' => $status,
            'is_featured' => $request->boolean('is_featured') && $request->user()->can('publish articles'),
            'published_at' => $status === 'published' ? now() : null,
        ]);

        return redirect()->route('knowledge.show', $article)->with('success', $this->statusMessage($status));
    }

    public function show(Request $request, KbArticle $article, MarkdownService $markdown): View
    {
        Gate::authorize('view', $article);

        $article->load(['author', 'category.parent']);

        if ($request->user()->id !== $article->user_id) {
            $article->incrementViews();
        }

        $breadcrumb = [];
        $cat = $article->category;
        while ($cat) {
            array_unshift($breadcrumb, $cat);
            $cat = $cat->parent;
        }

        return view('knowledge.show', [
            'article' => $article,
            'html' => $markdown->toHtml($article->content),
            'toc' => $markdown->extractHeadings($article->content),
            'breadcrumb' => $breadcrumb,
            'isFavorited' => $article->isFavoritedBy($request->user()->id),
        ]);
    }

    public function toggleFavorite(Request $request, KbArticle $article): RedirectResponse
    {
        Gate::authorize('view', $article);

        if ($article->isFavoritedBy($request->user()->id)) {
            $article->favoritedBy()->detach($request->user()->id);
            $message = 'Retiré des favoris.';
        } else {
            $article->favoritedBy()->attach($request->user()->id);
            $message = 'Ajouté aux favoris.';
        }

        return back()->with('success', $message);
    }

    public function edit(KbArticle $article): View
    {
        Gate::authorize('update', $article);

        $categories = KbCategory::where('is_active', true)->orderBy('lft')->get();

        return view('knowledge.edit', [
            'article' => $article,
            'categoryOptions' => $this->flatCategoryOptions($categories),
        ]);
    }

    public function update(Request $request, KbArticle $article): RedirectResponse
    {
        Gate::authorize('update', $article);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:kb_categories,id'],
            'content' => ['required', 'string'],
            'is_featured' => ['boolean'],
            'action' => ['required', 'in:draft,submit,publish'],
        ]);

        $status = $article->status;
        $publishedAt = $article->published_at;

        if ($data['action'] === 'draft') {
            $status = 'draft';
        } elseif ($data['action'] === 'submit') {
            $status = 'pending';
        } elseif ($data['action'] === 'publish' && $request->user()->can('publish articles')) {
            $status = 'published';
            $publishedAt = $publishedAt ?? now();
        }

        $article->update([
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'content' => $data['content'],
            'status' => $status,
            'is_featured' => $request->boolean('is_featured') && $request->user()->can('publish articles'),
            'published_at' => $publishedAt,
        ]);

        return redirect()->route('knowledge.show', $article)->with('success', $this->statusMessage($status));
    }

    public function destroy(KbArticle $article): RedirectResponse
    {
        Gate::authorize('delete', $article);

        $article->delete();

        return redirect()->route('knowledge.index')->with('success', 'Article supprimé.');
    }

    public function publish(KbArticle $article): RedirectResponse
    {
        Gate::authorize('publish', $article);

        $article->update([
            'status' => 'published',
            'published_at' => $article->published_at ?? now(),
        ]);

        return back()->with('success', "« {$article->title} » a été publié.");
    }

    // ==================== Helpers ====================

    protected function statusMessage(string $status): string
    {
        return match ($status) {
            'published' => 'Article publié avec succès.',
            'pending' => 'Article soumis pour validation.',
            default => 'Brouillon enregistré.',
        };
    }

    protected function flatCategoryOptions($categories, $parentId = null, $depth = 0): array
    {
        $result = [];
        foreach ($categories->where('parent_id', $parentId) as $category) {
            $result[$category->id] = str_repeat('— ', $depth) . $category->name;
            $result += $this->flatCategoryOptions($categories, $category->id, $depth + 1);
        }
        return $result;
    }

    protected function nestCategories($categories, $parentId = null): array
    {
        $result = [];
        foreach ($categories->where('parent_id', $parentId) as $category) {
            $result[] = [
                'model' => $category,
                'children' => $this->nestCategories($categories, $category->id),
            ];
        }
        return $result;
    }
}

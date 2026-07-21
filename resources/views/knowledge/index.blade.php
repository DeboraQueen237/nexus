@extends('layouts.app')

@section('title', 'Knowledge Base - NEXUS')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Knowledge Base</h1>
            <p class="mt-1 text-sm text-gray-500">Documentation collaborative en Markdown.</p>
        </div>
        <div class="flex gap-2">
            @can('manageCategories', \App\Models\KbArticle::class)
                <a href="{{ route('knowledge.categories.index') }}" class="btn-secondary">Catégories</a>
            @endcan
            @can('create', \App\Models\KbArticle::class)
                <a href="{{ route('knowledge.create') }}" class="btn-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Nouvel article
                </a>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
        {{-- Sidebar catégories --}}
        <aside class="lg:col-span-1">
            <div class="card sticky top-4">
                <div class="card-body">
                    <form method="GET" class="mb-4">
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher..." class="input-field text-sm">
                    </form>

                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">Catégories</p>
                    <div class="space-y-0.5">
                        <a href="{{ route('knowledge.index') }}" class="flex items-center rounded-lg px-3 py-1.5 text-sm {{ !request('category') ? 'bg-primary-50 font-semibold text-primary-700 dark:bg-primary-900/20 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-surface-800' }}">
                            Toutes les catégories
                        </a>
                        @include('knowledge._category-tree', ['nodes' => $categories])
                    </div>
                </div>
            </div>
        </aside>

        {{-- Contenu --}}
        <div class="lg:col-span-3">
            <div class="mb-4 flex flex-wrap gap-2 rounded-xl bg-gray-100 p-1 dark:bg-surface-800 max-w-xl">
                <a href="{{ route('knowledge.index', array_filter(array_merge(request()->query(), ['tab' => null]))) }}" class="flex-1 rounded-lg py-1.5 text-center text-sm font-medium transition {{ $tab === 'published' ? 'bg-white shadow-sm dark:bg-surface-900' : 'text-gray-500' }}">Publiés</a>
                <a href="{{ route('knowledge.index', array_filter(array_merge(request()->query(), ['tab' => 'mine']))) }}" class="flex-1 rounded-lg py-1.5 text-center text-sm font-medium transition {{ $tab === 'mine' ? 'bg-white shadow-sm dark:bg-surface-900' : 'text-gray-500' }}">Mes articles</a>
                <a href="{{ route('knowledge.index', array_filter(array_merge(request()->query(), ['tab' => 'favorites']))) }}" class="flex-1 rounded-lg py-1.5 text-center text-sm font-medium transition {{ $tab === 'favorites' ? 'bg-white shadow-sm dark:bg-surface-900' : 'text-gray-500' }}">Favoris</a>
                @if($canReview)
                    <a href="{{ route('knowledge.index', array_filter(array_merge(request()->query(), ['tab' => 'pending']))) }}" class="flex-1 rounded-lg py-1.5 text-center text-sm font-medium transition {{ $tab === 'pending' ? 'bg-white shadow-sm dark:bg-surface-900' : 'text-gray-500' }}">À valider</a>
                @endif
            </div>

            @if($articles->isEmpty())
                <div class="card">
                    <div class="card-body py-16 text-center text-gray-400">Aucun article ici pour le moment.</div>
                </div>
            @else
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach($articles as $article)
                        <a href="{{ route('knowledge.show', $article) }}" class="card animate-fade-in block">
                            <div class="card-body">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="badge-neutral">{{ $article->category->name }}</span>
                                    @if($article->status !== 'published')
                                        <span class="badge-{{ $article->status === 'pending' ? 'warning' : 'neutral' }}">
                                            {{ $article->status === 'pending' ? 'En attente' : 'Brouillon' }}
                                        </span>
                                    @endif
                                </div>
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $article->title }}</h3>
                                <p class="mt-2 line-clamp-2 text-sm text-gray-500">{{ Str::of($article->content)->stripTags()->limit(140) }}</p>
                                <div class="mt-3 flex items-center justify-between text-xs text-gray-400">
                                    <span>{{ $article->author->name }}</span>
                                    <span>{{ $article->views }} vue{{ $article->views > 1 ? 's' : '' }}</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-6">{{ $articles->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

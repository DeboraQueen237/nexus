@extends('layouts.app')

@section('title', $article->title . ' - NEXUS')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
    {{-- Fil d'Ariane --}}
    <nav class="mb-4 flex flex-wrap items-center gap-1 text-sm text-gray-500">
        <a href="{{ route('knowledge.index') }}" class="hover:text-primary-600">Knowledge Base</a>
        @foreach($breadcrumb as $crumb)
            <span>/</span>
            <a href="{{ route('knowledge.index', ['category' => $crumb->slug]) }}" class="hover:text-primary-600">{{ $crumb->name }}</a>
        @endforeach
    </nav>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-4">
        <div class="lg:col-span-3">
            <div class="card">
                <div class="card-body">
                    {{-- En-tête --}}
                    <div class="mb-6 flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-6 dark:border-surface-800">
                        <div>
                            @if($article->status !== 'published')
                                <span class="badge-{{ $article->status === 'pending' ? 'warning' : 'neutral' }} mb-2 inline-flex">
                                    {{ $article->status === 'pending' ? 'En attente de validation' : 'Brouillon' }}
                                </span>
                            @endif
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $article->title }}</h1>
                            <div class="mt-2 flex items-center gap-3 text-sm text-gray-500">
                                <div class="avatar h-7 w-7 text-xs">{{ Str::of($article->author->name)->substr(0, 1)->upper() }}</div>
                                <span>{{ $article->author->name }}</span>
                                <span>·</span>
                                <span>{{ $article->published_at?->diffForHumans() ?? $article->created_at->diffForHumans() }}</span>
                                <span>·</span>
                                <span>{{ $article->views }} vue{{ $article->views > 1 ? 's' : '' }}</span>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('knowledge.favorite', $article) }}">
                                @csrf
                                <button class="btn-icon {{ $isFavorited ? 'text-amber-500' : '' }}" title="{{ $isFavorited ? 'Retirer des favoris' : 'Ajouter aux favoris' }}">
                                    <svg class="h-5 w-5" fill="{{ $isFavorited ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" /></svg>
                                </button>
                            </form>
                            @can('update', $article)
                                <a href="{{ route('knowledge.edit', $article) }}" class="btn-secondary">Modifier</a>
                            @endcan
                            @can('publish', $article)
                                @if($article->status === 'pending')
                                    <form method="POST" action="{{ route('knowledge.publish', $article) }}">
                                        @csrf
                                        <button class="btn-primary">Publier</button>
                                    </form>
                                @endif
                            @endcan
                            @can('delete', $article)
                                <form method="POST" action="{{ route('knowledge.destroy', $article) }}" onsubmit="return confirm('Supprimer cet article ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn-icon text-red-400 hover:bg-red-50">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>

                    {{-- Contenu rendu --}}
                    <div class="prose prose-sm dark:prose-invert max-w-none">
                        {!! $html !!}
                    </div>
                </div>
            </div>
        </div>

        {{-- Sommaire --}}
        @if(count($toc))
            <aside class="lg:col-span-1">
                <div class="card sticky top-4">
                    <div class="card-body">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">Sur cette page</p>
                        <nav class="space-y-1 text-sm">
                            @foreach($toc as $heading)
                                <a href="#section-{{ $heading['slug'] }}" class="block truncate text-gray-500 hover:text-primary-600 {{ $heading['level'] == 3 ? 'pl-3' : '' }}">
                                    {{ $heading['text'] }}
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </div>
            </aside>
        @endif
    </div>
</div>
@endsection

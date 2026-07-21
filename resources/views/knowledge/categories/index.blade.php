@extends('layouts.app')

@section('title', 'Catégories - NEXUS')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
    <div class="mb-6">
        <a href="{{ route('knowledge.index') }}" class="text-sm text-gray-500 hover:text-primary-600">&larr; Retour à la Knowledge Base</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">Catégories</h1>
        <p class="mt-1 text-sm text-gray-500">Organise la hiérarchie de ta documentation.</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Formulaire de création --}}
        <div class="card h-fit">
            <div class="card-header"><h2 class="font-semibold text-gray-900 dark:text-gray-100">Nouvelle catégorie</h2></div>
            <form method="POST" action="{{ route('knowledge.categories.store') }}" class="card-body space-y-4">
                @csrf
                <div>
                    <x-input-label for="name" value="Nom" />
                    <x-text-input id="name" name="name" class="mt-1" required />
                </div>
                <div>
                    <x-input-label for="parent_id" value="Catégorie parente (optionnel)" />
                    <select id="parent_id" name="parent_id" class="input-field mt-1">
                        <option value="">— Aucune (catégorie racine) —</option>
                        @foreach($tree as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="description" value="Description (optionnel)" />
                    <textarea id="description" name="description" rows="2" class="input-field mt-1"></textarea>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded text-primary-600 focus:ring-primary-500">
                    Catégorie active
                </label>
                <button type="submit" class="btn-primary w-full">Créer</button>
            </form>
        </div>

        {{-- Liste --}}
        <div class="card h-fit">
            <div class="card-header"><h2 class="font-semibold text-gray-900 dark:text-gray-100">Catégories existantes ({{ $categories->count() }})</h2></div>
            <div class="divide-y divide-gray-100 dark:divide-surface-800">
                @forelse($categories as $category)
                    <div class="p-4" x-data="{ editing: false }">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-800 dark:text-gray-100">
                                    {{ $category->parent ? $category->parent->name . ' / ' : '' }}{{ $category->name }}
                                </p>
                                <p class="text-xs text-gray-400">{{ $category->articles_count }} article{{ $category->articles_count > 1 ? 's' : '' }}</p>
                            </div>
                            <div class="flex shrink-0 gap-1">
                                <button @click="editing = !editing" class="btn-icon h-8 w-8">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                                <form method="POST" action="{{ route('knowledge.categories.destroy', $category) }}" onsubmit="return confirm('Supprimer cette catégorie ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn-icon h-8 w-8 text-red-400 hover:bg-red-50">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <form x-show="editing" method="POST" action="{{ route('knowledge.categories.update', $category) }}" class="mt-3 space-y-2" style="display:none;">
                            @csrf
                            @method('PATCH')
                            <x-text-input name="name" value="{{ $category->name }}" class="w-full text-sm" required />
                            <select name="parent_id" class="input-field text-sm">
                                <option value="">— Aucune (catégorie racine) —</option>
                                @foreach($tree as $id => $label)
                                    <option value="{{ $id }}" @selected($category->parent_id === $id)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                                <input type="checkbox" name="is_active" value="1" {{ $category->is_active ? 'checked' : '' }} class="rounded text-primary-600 focus:ring-primary-500">
                                Active
                            </label>
                            <button type="submit" class="btn-primary w-full !py-1.5 text-sm">Enregistrer</button>
                        </form>
                    </div>
                @empty
                    <p class="p-6 text-center text-sm text-gray-400">Aucune catégorie pour le moment.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Planifier une réunion - NEXUS')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-8 sm:px-6"
     x-data="{
        selected: [],
        query: '',
        results: [],
        async search() {
            if (this.query.trim() === '') { this.results = []; return; }
            const res = await fetch(`/chat/users/search?q=${encodeURIComponent(this.query)}`, { headers: { Accept: 'application/json' } });
            this.results = res.ok ? await res.json() : [];
        },
        toggle(user) {
            const i = this.selected.findIndex(u => u.id === user.id);
            if (i === -1) this.selected.push(user); else this.selected.splice(i, 1);
        },
        isSelected(user) { return this.selected.some(u => u.id === user.id); },
     }"
>
    <div class="mb-6">
        <a href="{{ route('meetings.index') }}" class="text-sm text-gray-500 hover:text-primary-600">&larr; Retour aux réunions</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">Planifier une réunion</h1>
    </div>

    <form method="POST" action="{{ route('meetings.store') }}" class="card">
        @csrf
        <div class="card-body space-y-5">
            <div>
                <x-input-label for="title" value="Titre" />
                <x-text-input id="title" name="title" value="{{ old('title') }}" class="mt-1" required autofocus />
                <x-input-error :messages="$errors->get('title')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="description" value="Description (optionnel)" />
                <textarea id="description" name="description" rows="2" class="input-field mt-1">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="start_time" value="Début" />
                    <input type="datetime-local" id="start_time" name="start_time" value="{{ old('start_time') }}" class="input-field mt-1" required>
                    <x-input-error :messages="$errors->get('start_time')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="end_time" value="Fin (optionnel)" />
                    <input type="datetime-local" id="end_time" name="end_time" value="{{ old('end_time') }}" class="input-field mt-1">
                    <x-input-error :messages="$errors->get('end_time')" class="mt-1" />
                </div>
            </div>

            <div>
                <x-input-label value="Inviter des participants (optionnel)" />
                <input type="search" x-model="query" @input="search()" placeholder="Rechercher un utilisateur..." class="input-field mt-1">

                <template x-if="selected.length > 0">
                    <div class="mt-2 flex flex-wrap gap-2">
                        <template x-for="u in selected" :key="u.id">
                            <span @click="toggle(u)" x-text="u.name + ' ×'" class="badge-primary cursor-pointer"></span>
                        </template>
                    </div>
                </template>

                <div class="mt-2 max-h-48 space-y-1 overflow-y-auto" x-show="results.length > 0">
                    <template x-for="user in results" :key="user.id">
                        <button type="button" @click="toggle(user)"
                            class="flex w-full items-center gap-3 rounded-xl p-2 text-left hover:bg-gray-50 dark:hover:bg-surface-800"
                            :class="isSelected(user) ? 'bg-primary-50 dark:bg-primary-900/20' : ''">
                            <div class="avatar h-8 w-8 text-xs" x-text="user.name.charAt(0).toUpperCase()"></div>
                            <span class="text-sm text-gray-700 dark:text-gray-200" x-text="user.name"></span>
                        </button>
                    </template>
                </div>

                <template x-for="u in selected" :key="'field-' + u.id">
                    <input type="hidden" name="participants[]" :value="u.id">
                </template>
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <input type="checkbox" name="allow_link_join" value="1" checked class="rounded text-primary-600 focus:ring-primary-500">
                Autoriser toute personne ayant le lien d'invitation à rejoindre
            </label>
        </div>

        <div class="card-footer flex justify-end gap-3">
            <a href="{{ route('meetings.index') }}" class="btn-secondary">Annuler</a>
            <x-primary-button class="!w-auto">Planifier</x-primary-button>
        </div>
    </form>
</div>
@endsection

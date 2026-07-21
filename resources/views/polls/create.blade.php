@extends('layouts.app')

@section('title', 'Nouveau sondage - NEXUS')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-8 sm:px-6">
    <div class="mb-6">
        <a href="{{ route('polls.index') }}" class="text-sm text-gray-500 hover:text-primary-600">&larr; Retour aux sondages</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">Créer un sondage</h1>
    </div>

    <div class="card" x-data="{
        options: ['', ''],
        type: 'single',
        addOption() { if (this.options.length < 10) this.options.push(''); },
        removeOption(i) { if (this.options.length > 2) this.options.splice(i, 1); },
    }">
        <form method="POST" action="{{ route('polls.store') }}" class="card-body space-y-5">
            @csrf

            <div>
                <x-input-label for="title" value="Question" />
                <x-text-input id="title" name="title" value="{{ old('title') }}" class="mt-1" placeholder="Quelle est ta question ?" required autofocus />
                <x-input-error :messages="$errors->get('title')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="description" value="Description (optionnel)" />
                <textarea id="description" name="description" rows="2" class="input-field mt-1">{{ old('description') }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-1" />
            </div>

            <div>
                <x-input-label value="Options" />
                <div class="mt-1 space-y-2">
                    <template x-for="(option, i) in options" :key="i">
                        <div class="flex items-center gap-2">
                            <input type="text" :name="`options[${i}]`" x-model="options[i]" class="input-field" :placeholder="`Option ${i + 1}`" required>
                            <button type="button" @click="removeOption(i)" x-show="options.length > 2" class="btn-icon h-10 w-10 shrink-0 text-red-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="addOption()" x-show="options.length < 10" class="mt-2 text-sm font-medium text-primary-600 hover:text-primary-700">
                    + Ajouter une option
                </button>
                <x-input-error :messages="$errors->get('options')" class="mt-1" />
            </div>

            <div>
                <x-input-label value="Type de vote" />
                <div class="mt-1 flex gap-3">
                    <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-xl border border-gray-200 p-3 text-sm dark:border-surface-800" :class="type === 'single' ? 'border-primary-400 ring-1 ring-primary-300' : ''">
                        <input type="radio" name="type" value="single" x-model="type" class="text-primary-600 focus:ring-primary-500">
                        Choix unique
                    </label>
                    <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-xl border border-gray-200 p-3 text-sm dark:border-surface-800" :class="type === 'multiple' ? 'border-primary-400 ring-1 ring-primary-300' : ''">
                        <input type="radio" name="type" value="multiple" x-model="type" class="text-primary-600 focus:ring-primary-500">
                        Choix multiple
                    </label>
                </div>
            </div>

            <div class="flex flex-wrap gap-6">
                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <input type="checkbox" name="is_anonymous" value="1" {{ old('is_anonymous') ? 'checked' : '' }} class="rounded text-primary-600 focus:ring-primary-500">
                    Vote anonyme
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <input type="checkbox" name="is_public" value="1" checked class="rounded text-primary-600 focus:ring-primary-500">
                    Visible par tous
                </label>
            </div>

            <div>
                <x-input-label for="expires_at" value="Date de clôture (optionnel)" />
                <input type="datetime-local" id="expires_at" name="expires_at" value="{{ old('expires_at') }}" class="input-field mt-1">
                <x-input-error :messages="$errors->get('expires_at')" class="mt-1" />
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('polls.index') }}" class="btn-secondary">Annuler</a>
                <x-primary-button class="!w-auto">Publier le sondage</x-primary-button>
            </div>
        </form>
    </div>
</div>
@endsection

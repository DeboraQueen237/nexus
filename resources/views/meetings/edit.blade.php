@extends('layouts.app')

@section('title', 'Modifier - ' . $meeting->title . ' - NEXUS')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-8 sm:px-6">
    <div class="mb-6">
        <a href="{{ route('meetings.show', $meeting) }}" class="text-sm text-gray-500 hover:text-primary-600">&larr; Retour à la réunion</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">Modifier la réunion</h1>
    </div>

    <form method="POST" action="{{ route('meetings.update', $meeting) }}" class="card">
        @csrf
        @method('PUT')
        <div class="card-body space-y-5">
            <div>
                <x-input-label for="title" value="Titre" />
                <x-text-input id="title" name="title" value="{{ old('title', $meeting->title) }}" class="mt-1" required autofocus />
                <x-input-error :messages="$errors->get('title')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="description" value="Description (optionnel)" />
                <textarea id="description" name="description" rows="2" class="input-field mt-1">{{ old('description', $meeting->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="start_time" value="Début" />
                    <input type="datetime-local" id="start_time" name="start_time" value="{{ old('start_time', $meeting->start_time->format('Y-m-d\TH:i')) }}" class="input-field mt-1" required>
                    <x-input-error :messages="$errors->get('start_time')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="end_time" value="Fin (optionnel)" />
                    <input type="datetime-local" id="end_time" name="end_time" value="{{ old('end_time', $meeting->end_time?->format('Y-m-d\TH:i')) }}" class="input-field mt-1">
                    <x-input-error :messages="$errors->get('end_time')" class="mt-1" />
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <input type="checkbox" name="allow_link_join" value="1" {{ old('allow_link_join', $meeting->allow_link_join) ? 'checked' : '' }} class="rounded text-primary-600 focus:ring-primary-500">
                Autoriser toute personne ayant le lien d'invitation à rejoindre
            </label>
        </div>

        <div class="card-footer flex justify-end gap-3">
            <a href="{{ route('meetings.show', $meeting) }}" class="btn-secondary">Annuler</a>
            <x-primary-button class="!w-auto">Enregistrer</x-primary-button>
        </div>
    </form>
</div>
@endsection

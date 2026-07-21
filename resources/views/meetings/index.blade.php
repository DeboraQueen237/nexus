@extends('layouts.app')

@section('title', 'Réunions - NEXUS')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Réunions</h1>
            <p class="mt-1 text-sm text-gray-500">Planifie et rejoins tes appels vidéo, vocaux et partages d'écran.</p>
        </div>
        @can('create', \App\Models\Meeting::class)
            <a href="{{ route('meetings.create') }}" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Planifier une réunion
            </a>
        @endcan
    </div>

    <div class="mb-4">
        <a href="{{ route('meetings.index', ['view' => 'calendar']) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">
            📅 Voir le calendrier mensuel
        </a>
    </div>

    <div class="mb-6 flex gap-2 rounded-xl bg-gray-100 p-1 dark:bg-surface-800 max-w-xs">
        <a href="{{ route('meetings.index') }}" class="flex-1 rounded-lg py-1.5 text-center text-sm font-medium transition {{ $tab !== 'past' ? 'bg-white shadow-sm dark:bg-surface-900' : 'text-gray-500' }}">À venir</a>
        <a href="{{ route('meetings.index', ['tab' => 'past']) }}" class="flex-1 rounded-lg py-1.5 text-center text-sm font-medium transition {{ $tab === 'past' ? 'bg-white shadow-sm dark:bg-surface-900' : 'text-gray-500' }}">Passées</a>
    </div>

    @if($meetings->isEmpty())
        <div class="card">
            <div class="card-body py-16 text-center text-gray-400">
                {{ $tab === 'past' ? 'Aucune réunion passée.' : 'Aucune réunion prévue.' }}
            </div>
        </div>
    @else
        <div class="space-y-3">
            @foreach($meetings as $meeting)
                <a href="{{ route('meetings.show', $meeting) }}" class="card block animate-fade-in">
                    <div class="card-body flex items-center gap-4">
                        <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-xl bg-gradient-to-br from-primary-50 to-secondary-50 text-primary-700 dark:from-primary-900/30 dark:to-secondary-900/30">
                            <span class="text-xs font-medium uppercase">{{ $meeting->start_time->translatedFormat('M') }}</span>
                            <span class="text-lg font-bold leading-none">{{ $meeting->start_time->format('d') }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate font-semibold text-gray-800 dark:text-gray-100">{{ $meeting->title }}</p>
                                @if($meeting->status === 'ongoing')
                                    <span class="badge-success">En cours</span>
                                @elseif($meeting->status === 'cancelled')
                                    <span class="badge-danger">Annulée</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500">
                                {{ $meeting->start_time->format('H:i') }}
                                @if($meeting->end_time) — {{ $meeting->end_time->format('H:i') }} @endif
                                · {{ $meeting->organizer->name }}
                            </p>
                        </div>
                        <svg class="h-5 w-5 shrink-0 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">{{ $meetings->links() }}</div>
    @endif
</div>
@endsection

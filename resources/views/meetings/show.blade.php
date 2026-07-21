@extends('layouts.app')

@section('title', $meeting->title . ' - NEXUS')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-8 sm:px-6" x-data="{ linkCopied: false, copyLink() { navigator.clipboard.writeText('{{ $meeting->inviteUrl() }}'); this.linkCopied = true; setTimeout(() => this.linkCopied = false, 1500); } }">
    <div class="mb-6">
        <a href="{{ route('meetings.index') }}" class="text-sm text-gray-500 hover:text-primary-600">&larr; Retour aux réunions</a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="mb-4 flex flex-wrap items-start justify-between gap-4">
                <div>
                    @if($meeting->status === 'ongoing')
                        <span class="badge-success mb-2 inline-flex">● En cours</span>
                    @elseif($meeting->status === 'cancelled')
                        <span class="badge-danger mb-2 inline-flex">Annulée</span>
                    @elseif($meeting->status === 'ended')
                        <span class="badge-neutral mb-2 inline-flex">Terminée</span>
                    @endif
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $meeting->title }}</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ $meeting->start_time->translatedFormat('l d F Y') }} à {{ $meeting->start_time->format('H:i') }}
                        @if($meeting->end_time) — {{ $meeting->end_time->format('H:i') }} @endif
                    </p>
                </div>
                <div class="avatar h-10 w-10">{{ Str::of($meeting->organizer->name)->substr(0, 1)->upper() }}</div>
            </div>

            @if($meeting->description)
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">{{ $meeting->description }}</p>
            @endif

            {{-- Lien d'invitation --}}
            @if($meeting->allow_link_join && $meeting->status !== 'cancelled')
                <div class="mb-4 flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-surface-800 dark:bg-surface-800/50">
                    <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m5.656 0a4 4 0 000-5.656l-1.5-1.5a4 4 0 00-5.656 5.656l3 3" /></svg>
                    <span class="flex-1 truncate text-xs text-gray-500">{{ $meeting->inviteUrl() }}</span>
                    <button @click="copyLink()" class="text-xs font-semibold text-primary-600 hover:text-primary-700">
                        <span x-show="!linkCopied">Copier</span>
                        <span x-show="linkCopied" style="display:none;">Copié ✓</span>
                    </button>
                </div>
            @endif

            {{-- Participants --}}
            @if($meeting->participants->isNotEmpty())
                <div class="mb-4">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">Participants ({{ $meeting->participants->count() }})</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($meeting->participants as $participant)
                            <span class="badge-{{ $participant->pivot->status === 'accepted' || $participant->pivot->status === 'joined' ? 'success' : ($participant->pivot->status === 'declined' ? 'danger' : 'neutral') }}">
                                {{ $participant->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- RSVP --}}
            @if($myStatus === 'invited')
                <div class="mb-4 flex gap-3">
                    <form method="POST" action="{{ route('meetings.respond', $meeting) }}">
                        @csrf
                        <input type="hidden" name="status" value="accepted">
                        <button class="btn-secondary">✓ Accepter l'invitation</button>
                    </form>
                    <form method="POST" action="{{ route('meetings.respond', $meeting) }}">
                        @csrf
                        <input type="hidden" name="status" value="declined">
                        <button class="btn-ghost">Décliner</button>
                    </form>
                </div>
            @endif

            {{-- Actions --}}
            <div class="flex flex-wrap gap-3">
                @if($canJoinNow)
                    <a href="{{ route('meetings.room', $meeting) }}" class="btn-primary">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                        Rejoindre la réunion
                    </a>
                @endif

                @can('update', $meeting)
                    <a href="{{ route('meetings.edit', $meeting) }}" class="btn-secondary">Modifier</a>
                @endcan

                @if($meeting->status === 'ongoing')
                    @can('update', $meeting)
                        <form method="POST" action="{{ route('meetings.end', $meeting) }}">
                            @csrf
                            <button class="btn-secondary">Terminer pour tous</button>
                        </form>
                    @endcan
                @endif

                @can('delete', $meeting)
                    @if($meeting->status !== 'cancelled')
                        <form method="POST" action="{{ route('meetings.destroy', $meeting) }}" onsubmit="return confirm('Annuler cette réunion ?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn-ghost text-red-500">Annuler la réunion</button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection

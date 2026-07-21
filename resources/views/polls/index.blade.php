@extends('layouts.app')

@section('title', 'Sondages - NEXUS')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-8 sm:px-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Sondages</h1>
            <p class="mt-1 text-sm text-gray-500">Vote et suis les résultats en direct.</p>
        </div>
        @can('create', \App\Models\Poll::class)
            <a href="{{ route('polls.create') }}" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Nouveau sondage
            </a>
        @endcan
    </div>

    <div class="mb-6 flex gap-2 rounded-xl bg-gray-100 p-1 dark:bg-surface-800 max-w-xs">
        <a href="{{ route('polls.index') }}" class="flex-1 rounded-lg py-1.5 text-center text-sm font-medium transition {{ request('filter') !== 'mine' ? 'bg-white shadow-sm dark:bg-surface-900' : 'text-gray-500' }}">
            Tous
        </a>
        <a href="{{ route('polls.index', ['filter' => 'mine']) }}" class="flex-1 rounded-lg py-1.5 text-center text-sm font-medium transition {{ request('filter') === 'mine' ? 'bg-white shadow-sm dark:bg-surface-900' : 'text-gray-500' }}">
            Mes sondages
        </a>
    </div>

    @if($polls->isEmpty())
        <div class="card">
            <div class="card-body py-16 text-center">
                <p class="text-gray-400">Aucun sondage pour le moment.</p>
                @can('create', \App\Models\Poll::class)
                    <a href="{{ route('polls.create') }}" class="btn-primary mt-4 inline-flex">Créer le premier sondage</a>
                @endcan
            </div>
        </div>
    @else
        <div class="space-y-4">
            @foreach($polls as $poll)
                @include('polls._card', ['poll' => $poll])
            @endforeach
        </div>

        <div class="mt-6">
            {{ $polls->links() }}
        </div>
    @endif
</div>
@endsection

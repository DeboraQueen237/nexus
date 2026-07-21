@extends('layouts.app')

@section('title', 'Calendrier - NEXUS')

@php
    $startOfGrid = $cursor->copy()->startOfMonth()->startOfWeek();
    $endOfGrid = $cursor->copy()->endOfMonth()->endOfWeek();
    $today = now()->format('Y-m-d');
@endphp

@section('content')
<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Calendrier des réunions</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $cursor->translatedFormat('F Y') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('meetings.index') }}" class="btn-secondary">Vue liste</a>
            @can('create', \App\Models\Meeting::class)
                <a href="{{ route('meetings.create') }}" class="btn-primary">Planifier</a>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-header flex items-center justify-between">
            <a href="{{ route('meetings.index', ['view' => 'calendar', 'month' => $cursor->copy()->subMonth()->month, 'year' => $cursor->copy()->subMonth()->year]) }}" class="btn-icon">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $cursor->translatedFormat('F Y') }}</span>
            <a href="{{ route('meetings.index', ['view' => 'calendar', 'month' => $cursor->copy()->addMonth()->month, 'year' => $cursor->copy()->addMonth()->year]) }}" class="btn-icon">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </a>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-7 gap-1 text-center text-xs font-semibold uppercase text-gray-400">
                @foreach(['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'] as $day)
                    <div class="py-2">{{ $day }}</div>
                @endforeach
            </div>
            <div class="grid grid-cols-7 gap-1">
                @php $day = $startOfGrid->copy(); @endphp
                @while($day <= $endOfGrid)
                    @php
                        $key = $day->format('Y-m-d');
                        $dayMeetingsCount = $meetingsByDay->get($key, collect())->count();
                        $isCurrentMonth = $day->month === $cursor->month;
                    @endphp
                    <a href="{{ route('meetings.index', ['view' => 'calendar', 'month' => $cursor->month, 'year' => $cursor->year, 'date' => $key]) }}"
                       class="flex aspect-square flex-col items-center justify-center rounded-lg text-sm transition
                              {{ $selectedDate === $key ? 'bg-primary-600 text-white' : ($key === $today ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30' : ($isCurrentMonth ? 'hover:bg-gray-100 dark:hover:bg-surface-800' : 'text-gray-300 dark:text-gray-700')) }}">
                        <span>{{ $day->day }}</span>
                        @if($dayMeetingsCount > 0)
                            <span class="mt-0.5 h-1.5 w-1.5 rounded-full {{ $selectedDate === $key ? 'bg-white' : 'bg-primary-500' }}"></span>
                        @endif
                    </a>
                    @php $day->addDay(); @endphp
                @endwhile
            </div>
        </div>
    </div>

    @if($selectedDate)
        <div class="mt-6">
            <p class="mb-3 text-sm font-semibold text-gray-600 dark:text-gray-300">
                Réunions du {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d F Y') }}
            </p>
            @forelse($dayMeetings as $meeting)
                <a href="{{ route('meetings.show', $meeting) }}" class="card mb-2 block">
                    <div class="card-body flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-800 dark:text-gray-100">{{ $meeting->title }}</p>
                            <p class="text-sm text-gray-500">{{ $meeting->start_time->format('H:i') }}</p>
                        </div>
                        @if($meeting->status === 'ongoing')
                            <span class="badge-success">En cours</span>
                        @endif
                    </div>
                </a>
            @empty
                <p class="text-sm text-gray-400">Aucune réunion ce jour-là.</p>
            @endforelse
        </div>
    @endif
</div>
@endsection

@extends('layouts.app')

@section('title', ($title ?? 'Module') . ' - NEXUS')

@section('content')
<div class="flex min-h-[70vh] items-center justify-center px-4">
    <div class="text-center">
        <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-100 to-secondary-100 text-3xl dark:from-primary-900/40 dark:to-secondary-900/40">
            {{ $icon ?? '🚧' }}
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $title ?? 'Module en construction' }}</h1>
        <p class="mx-auto mt-2 max-w-md text-sm text-gray-500">
            {{ $description ?? "Ce module arrive dans une prochaine étape du développement de NEXUS." }}
        </p>
        <a href="{{ route('dashboard') }}" class="btn-primary mt-6 inline-flex">Retour au tableau de bord</a>
    </div>
</div>
@endsection

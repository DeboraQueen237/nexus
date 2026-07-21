@extends('layouts.app')

@section('title', $poll['title'] . ' - NEXUS')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-8 sm:px-6">
    <div class="mb-6">
        <a href="{{ route('polls.index') }}" class="text-sm text-gray-500 hover:text-primary-600">&larr; Tous les sondages</a>
    </div>

    @include('polls._card', ['poll' => $poll])
</div>
@endsection

@extends('layouts.app')

@section('title', 'Nouvel article - NEXUS')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6" x-data="markdownEditor(@js(old('content', '')))">
    <div class="mb-6">
        <a href="{{ route('knowledge.index') }}" class="text-sm text-gray-500 hover:text-primary-600">&larr; Retour à la Knowledge Base</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">Nouvel article</h1>
    </div>

    <form method="POST" action="{{ route('knowledge.store') }}">
        @csrf
        @include('knowledge._form', ['categoryOptions' => $categoryOptions])
    </form>
</div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/marked/16.3.0/lib/marked.umd.min.js"></script>
@endpush

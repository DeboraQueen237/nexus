@extends('layouts.app')

@section('title', 'Modifier - ' . $article->title . ' - NEXUS')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6" x-data="markdownEditor(@js(old('content', $article->content)))">
    <div class="mb-6">
        <a href="{{ route('knowledge.show', $article) }}" class="text-sm text-gray-500 hover:text-primary-600">&larr; Retour à l'article</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">Modifier l'article</h1>
    </div>

    <form method="POST" action="{{ route('knowledge.update', $article) }}">
        @csrf
        @method('PUT')
        @include('knowledge._form', ['categoryOptions' => $categoryOptions, 'article' => $article])
    </form>
</div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/marked/16.3.0/lib/marked.umd.min.js"></script>
@endpush

@extends('layouts.app')

@section('title', 'Dashboard - NEXUS')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- En-tête -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    Bienvenue sur NEXUS, {{ auth()->user()->name }}! 👋
                </h1>
                <p class="text-gray-600 mt-1">Plateforme de collaboration ultime</p>
            </div>
            <span class="badge badge-success px-4 py-2 text-sm">
                ● En ligne
            </span>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="card">
                <div class="card-body flex items-center">
                    <div class="p-3 bg-primary-100 rounded-xl">
                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Messages</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $stats['messages'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body flex items-center">
                    <div class="p-3 bg-green-100 rounded-xl">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Sondages</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $stats['polls'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body flex items-center">
                    <div class="p-3 bg-purple-100 rounded-xl">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Articles</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $stats['articles'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-xl">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Réunions</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $stats['meetings'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions Rapides -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="text-lg font-semibold text-gray-900">🚀 Actions Rapides</h3>
                </div>
                <div class="card-body grid grid-cols-2 gap-4">
                    <a href="{{ route('chat.index') }}" class="p-4 text-center bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <span class="text-3xl block mb-2">💬</span>
                        <span class="text-sm font-medium text-gray-700">Nouveau Message</span>
                    </a>
                    <a href="{{ route('polls.create') }}" class="p-4 text-center bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <span class="text-3xl block mb-2">📊</span>
                        <span class="text-sm font-medium text-gray-700">Créer un Sondage</span>
                    </a>
                    <a href="{{ route('knowledge.create') }}" class="p-4 text-center bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <span class="text-3xl block mb-2">📚</span>
                        <span class="text-sm font-medium text-gray-700">Nouvel Article</span>
                    </a>
                    <a href="{{ route('meetings.create') }}" class="p-4 text-center bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <span class="text-3xl block mb-2">📅</span>
                        <span class="text-sm font-medium text-gray-700">Planifier Réunion</span>
                    </a>
                </div>
            </div>

            <!-- Activité récente -->
            <div class="card">
                <div class="card-header">
                    <h3 class="text-lg font-semibold text-gray-900">🔄 Activité Récente</h3>
                </div>
                <div class="card-body">
                    <div class="space-y-3">
                        @forelse($recentActivity['messages'] ?? [] as $message)
                            <div class="flex items-center text-sm">
                                <span class="text-gray-500">💬</span>
                                <span class="ml-2 text-gray-700 truncate">{{ Str::limit($message->content, 50) }}</span>
                                <span class="ml-auto text-gray-400 text-xs">{{ $message->created_at->diffForHumans() }}</span>
                            </div>
                        @empty
                            <p class="text-gray-500 text-sm">Aucune activité récente</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
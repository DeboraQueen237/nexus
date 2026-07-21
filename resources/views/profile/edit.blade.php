@extends('layouts.app')

@section('title', 'Mon profil - NEXUS')

@section('content')
<div class="py-8">
    <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Mon profil</h1>
            <p class="mt-1 text-sm text-gray-500">Gérez vos informations personnelles et la sécurité de votre compte.</p>
        </div>

        <div class="card">
            <div class="card-body">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                @include('profile.partials.two-factor-form')
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Activité récente</h2>
                <p class="mt-1 text-sm text-gray-500">Dernières connexions détectées sur ton compte.</p>
                <div class="mt-4 divide-y divide-gray-100 dark:divide-surface-800">
                    @forelse($recentLogins as $login)
                        <div class="flex items-center justify-between py-3 text-sm">
                            <div>
                                <p class="font-medium text-gray-700 dark:text-gray-200">{{ $login->ip_address ?? 'IP inconnue' }}</p>
                                <p class="text-xs text-gray-400">{{ Str::limit($login->user_agent, 60) }}</p>
                            </div>
                            <div class="text-right">
                                @if($login->status === 'success')
                                    <span class="badge-success">Connexion réussie</span>
                                @elseif($login->status === 'failed_2fa')
                                    <span class="badge-warning">Échec 2FA</span>
                                @else
                                    <span class="badge-danger">Mot de passe incorrect</span>
                                @endif
                                <p class="mt-1 text-xs text-gray-400">{{ $login->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-center text-sm text-gray-400">Aucune activité enregistrée pour le moment.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="card border-red-100 dark:border-red-900/40">
            <div class="card-body">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</div>
@endsection

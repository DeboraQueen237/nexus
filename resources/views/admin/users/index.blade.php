@extends('layouts.app')

@section('title', 'Utilisateurs & Rôles - NEXUS')

@section('content')
<div class="py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Utilisateurs & Rôles</h1>
                <p class="mt-1 text-sm text-gray-500">Gérez les niveaux d'accès de chaque membre de la plateforme.</p>
            </div>
            <form method="GET" class="w-full sm:w-72">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Rechercher un utilisateur..." class="input-field text-sm" onchange="this.form.submit()" />
            </form>
        </div>

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50 text-xs uppercase tracking-wider text-gray-500 dark:border-surface-800 dark:bg-surface-800/50">
                        <tr>
                            <th class="px-6 py-3">Utilisateur</th>
                            <th class="px-6 py-3">Rôle actuel</th>
                            <th class="px-6 py-3">Membre depuis</th>
                            <th class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-surface-800">
                        @forelse($users as $user)
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-surface-800/40">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="avatar h-9 w-9 text-xs">{{ Str::of($user->name)->substr(0, 1)->upper() }}</div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="badge-primary">{{ $user->getRoleNames()->first() ?? 'Aucun' }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-500">{{ $user->created_at->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-right">
                                    <form method="POST" action="{{ route('admin.users.role', $user) }}" class="inline-flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" class="input-field w-40 py-1.5 text-sm" onchange="this.form.submit()">
                                            @foreach($roles as $role)
                                                <option value="{{ $role->name }}" @selected($user->hasRole($role->name))>{{ ucfirst($role->name) }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-400">Aucun utilisateur trouvé.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
                <div class="card-footer">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

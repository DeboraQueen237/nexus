<!DOCTYPE html>
<html lang="fr" x-data="{ darkMode: $persist(false) }" :class="{ 'dark': darkMode }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'NEXUS'))</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
    </head>
    <body class="font-sans antialiased" x-cloak>
        <div class="flex h-screen overflow-hidden bg-surface-50 dark:bg-surface-950">

            {{-- ===== Sidebar desktop ===== --}}
            <aside class="hidden w-72 shrink-0 flex-col border-r border-gray-100 bg-white dark:border-surface-800 dark:bg-surface-900 lg:flex">
                <div class="flex h-16 items-center gap-2 border-b border-gray-100 px-6 dark:border-surface-800">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-primary-600 to-secondary-600 text-white font-bold shadow-soft">N</div>
                    <span class="text-lg font-bold gradient-text">NEXUS</span>
                </div>
                <div class="flex flex-1 flex-col overflow-y-auto py-4">
                    @include('layouts.partials.sidebar-nav')
                </div>
                <div class="border-t border-gray-100 p-4 dark:border-surface-800">
                    <div class="flex items-center gap-3 rounded-xl bg-gray-50 p-3 dark:bg-surface-800">
                        <div class="avatar">{{ Str::of(auth()->user()->name)->substr(0, 1)->upper() }}</div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->getRoleNames()->first() ?? 'Membre' }}</p>
                        </div>
                    </div>
                </div>
            </aside>

            {{-- ===== Tiroir mobile ===== --}}
            <div x-data="{ open: false }" @keydown.escape.window="open = false" x-init="window.addEventListener('open-mobile-nav', () => open = true)">
                <div x-show="open" x-transition.opacity class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden" @click="open = false" style="display: none;"></div>
                <aside x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-white dark:bg-surface-900 lg:hidden" style="display: none;">
                    <div class="flex h-16 items-center justify-between border-b border-gray-100 px-6 dark:border-surface-800">
                        <div class="flex items-center gap-2">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-primary-600 to-secondary-600 text-white font-bold">N</div>
                            <span class="text-lg font-bold gradient-text">NEXUS</span>
                        </div>
                        <button @click="open = false" class="btn-icon">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto py-4">
                        @include('layouts.partials.sidebar-nav')
                    </div>
                </aside>
            </div>

            {{-- ===== Zone principale ===== --}}
            <div class="flex flex-1 flex-col overflow-hidden">
                @include('layouts.partials.topbar')

                @isset($header)
                    <header class="border-b border-gray-100 bg-white px-4 py-4 dark:border-surface-800 dark:bg-surface-900 sm:px-6 lg:px-8">
                        {{ $header }}
                    </header>
                @endisset

                <main class="flex-1 overflow-y-auto">
                    @if (session('success'))
                        <div class="mx-4 mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 sm:mx-6 lg:mx-8" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="mx-4 mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:bg-red-900/30 dark:text-red-300 sm:mx-6 lg:mx-8">
                            {{ session('error') }}
                        </div>
                    @endif

                    @yield('content')
                    {{ $slot ?? '' }}
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>

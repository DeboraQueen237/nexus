<header class="flex h-16 shrink-0 items-center gap-4 border-b border-gray-100 bg-white px-4 dark:border-surface-800 dark:bg-surface-900 sm:px-6 lg:px-8">
    {{-- Bouton menu mobile --}}
    <button @click="window.dispatchEvent(new CustomEvent('open-mobile-nav'))" class="btn-icon lg:hidden">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    {{-- Recherche globale (câblée module par module au fil du projet) --}}
    <div class="hidden flex-1 max-w-md sm:block">
        <div class="relative">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
            </svg>
            <input type="search" placeholder="Rechercher dans NEXUS..." class="input-field pl-9 py-2 text-sm" />
        </div>
    </div>

    <div class="ml-auto flex items-center gap-2">
        {{-- Bascule mode sombre --}}
        <button @click="darkMode = !darkMode" class="btn-icon" aria-label="Basculer le mode sombre">
            <svg x-show="!darkMode" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
            <svg x-show="darkMode" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
        </button>

        {{-- Notifications (branchées aux vrais événements avec le module Chat/Réunions) --}}
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="btn-icon relative" aria-label="Notifications">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
            </button>
            <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 z-50 mt-2 w-80 rounded-xl2 border border-gray-100 bg-white p-2 shadow-soft dark:border-surface-800 dark:bg-surface-900" style="display: none;">
                <p class="px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200">Notifications</p>
                <div class="px-3 py-8 text-center text-sm text-gray-400">
                    Aucune notification pour le moment
                </div>
            </div>
        </div>

        {{-- Menu utilisateur --}}
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex items-center gap-2 rounded-xl p-1.5 transition hover:bg-gray-100 dark:hover:bg-surface-800">
                <div class="avatar h-8 w-8 text-xs">{{ Str::of(auth()->user()->name)->substr(0, 1)->upper() }}</div>
            </button>
            <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 z-50 mt-2 w-56 rounded-xl2 border border-gray-100 bg-white p-2 shadow-soft dark:border-surface-800 dark:bg-surface-900" style="display: none;">
                <div class="border-b border-gray-100 px-3 py-2 dark:border-surface-800">
                    <p class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->email }}</p>
                </div>
                <a href="{{ route('profile.edit') }}" class="mt-1 block rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-surface-800">
                    Mon profil
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                        Se déconnecter
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

<x-guest-layout>
    <div class="mb-6 text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-primary-100 to-secondary-100 dark:from-primary-900/40 dark:to-secondary-900/40">
            <svg class="h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
        </div>
        <h1 class="text-lg font-bold text-gray-900 dark:text-gray-100">Vérification en deux étapes</h1>
        <p class="mt-1 text-sm text-gray-500">Entre le code à 6 chiffres généré par ton application d'authentification.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300">
            {{ $errors->first() }}
        </div>
    @endif

    <div x-data="{ useRecovery: false }">
        <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-4">
            @csrf

            <div x-show="!useRecovery">
                <x-input-label for="code" value="Code d'authentification" />
                <x-text-input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" class="text-center text-lg tracking-[0.5em]" maxlength="6" autofocus />
            </div>

            <div x-show="useRecovery" style="display: none;">
                <x-input-label for="recovery_code" value="Code de récupération" />
                <x-text-input id="recovery_code" name="recovery_code" type="text" class="text-center" placeholder="xxxx-xxxx" />
            </div>

            <x-primary-button>Vérifier</x-primary-button>

            <button type="button" @click="useRecovery = !useRecovery" class="w-full text-center text-sm text-gray-500 hover:text-primary-600">
                <span x-show="!useRecovery">J'ai perdu l'accès à mon application, utiliser un code de récupération</span>
                <span x-show="useRecovery" style="display: none;">Utiliser plutôt le code de mon application</span>
            </button>
        </form>
    </div>

    <div class="mt-6 border-t border-gray-100 pt-4 text-center dark:border-surface-800">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-gray-400 hover:text-gray-600">Annuler et se déconnecter</button>
        </form>
    </div>
</x-guest-layout>

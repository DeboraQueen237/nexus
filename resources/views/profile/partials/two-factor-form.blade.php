@php
    $twoFactorService = app(\App\Services\TwoFactorAuthenticationService::class);
    $pendingSecret = session('2fa.setup.secret');
    $recoveryCodesToShow = session('2fa.setup.recovery_codes');
@endphp

<section>
    <header class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Authentification à deux facteurs</h2>
            <p class="mt-1 text-sm text-gray-500">
                Ajoute une couche de sécurité supplémentaire à ton compte avec une application comme Google Authenticator ou Authy.
            </p>
        </div>
        @if ($user->hasTwoFactorEnabled())
            <span class="badge-success">Activée</span>
        @else
            <span class="badge-neutral">Désactivée</span>
        @endif
    </header>

    <div class="mt-6">
        @if ($recoveryCodesToShow)
            {{-- Affiché une seule fois juste après activation/régénération --}}
            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-900/20">
                <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                    ⚠️ Sauvegarde ces codes de récupération maintenant — ils ne seront plus jamais affichés.
                </p>
                <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">
                    Chaque code ne peut être utilisé qu'une seule fois si tu perds l'accès à ton application d'authentification.
                </p>
                <div class="mt-3 grid grid-cols-2 gap-2 rounded-lg bg-white p-3 font-mono text-sm dark:bg-surface-900 sm:grid-cols-4">
                    @foreach ($recoveryCodesToShow as $recoveryCode)
                        <span>{{ $recoveryCode }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        @if (! $user->hasTwoFactorEnabled())
            @if ($pendingSecret)
                {{-- Étape de confirmation : QR code + saisie du code --}}
                <div class="flex flex-col items-start gap-6 sm:flex-row">
                    <div class="rounded-xl border border-gray-100 p-4 dark:border-surface-800">
                        {!! $twoFactorService->qrCodeSvg($user, $pendingSecret) !!}
                    </div>
                    <div class="flex-1">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            1. Scanne ce QR code avec ton application d'authentification.<br>
                            2. Ou saisis manuellement ce code : <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs dark:bg-surface-800">{{ $pendingSecret }}</code><br>
                            3. Entre le code à 6 chiffres généré pour confirmer :
                        </p>
                        <form method="POST" action="{{ route('two-factor.confirm') }}" class="mt-3 flex items-end gap-3">
                            @csrf
                            <div class="flex-1 max-w-[160px]">
                                <x-input-label for="twofa_code" value="Code" />
                                <x-text-input id="twofa_code" name="code" type="text" inputmode="numeric" maxlength="6" class="text-center tracking-[0.4em]" autofocus />
                                <x-input-error :messages="$errors->get('code')" class="mt-1" />
                            </div>
                            <x-primary-button>Confirmer</x-primary-button>
                        </form>
                    </div>
                </div>
            @else
                <form method="POST" action="{{ route('two-factor.enable') }}">
                    @csrf
                    <x-primary-button type="submit">Activer la 2FA</x-primary-button>
                </form>
            @endif
        @else
            <div class="flex flex-wrap gap-3">
                <button type="button" x-data @click="$dispatch('open-modal', 'confirm-recovery-codes')" class="btn-secondary">
                    Régénérer les codes de récupération
                </button>
                <button type="button" x-data @click="$dispatch('open-modal', 'confirm-disable-2fa')" class="btn-danger">
                    Désactiver la 2FA
                </button>
            </div>

            {{-- Modale : régénérer les codes --}}
            <x-modal name="confirm-recovery-codes" focusable>
                <form method="POST" action="{{ route('two-factor.recovery-codes') }}" class="p-6">
                    @csrf
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Régénérer les codes de récupération ?</h2>
                    <p class="mt-1 text-sm text-gray-500">Les anciens codes deviendront invalides. Confirme avec ton mot de passe.</p>
                    <div class="mt-4">
                        <x-input-label for="regen_password" value="Mot de passe" />
                        <x-text-input id="regen_password" name="password" type="password" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <x-secondary-button x-on:click="$dispatch('close')">Annuler</x-secondary-button>
                        <x-primary-button>Régénérer</x-primary-button>
                    </div>
                </form>
            </x-modal>

            {{-- Modale : désactiver --}}
            <x-modal name="confirm-disable-2fa" focusable>
                <form method="POST" action="{{ route('two-factor.disable') }}" class="p-6">
                    @csrf
                    @method('DELETE')
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Désactiver la 2FA ?</h2>
                    <p class="mt-1 text-sm text-gray-500">Ton compte sera moins protégé. Confirme avec ton mot de passe.</p>
                    <div class="mt-4">
                        <x-input-label for="disable_password" value="Mot de passe" />
                        <x-text-input id="disable_password" name="password" type="password" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <x-secondary-button x-on:click="$dispatch('close')">Annuler</x-secondary-button>
                        <x-danger-button>Désactiver</x-danger-button>
                    </div>
                </form>
            </x-modal>
        @endif
    </div>
</section>

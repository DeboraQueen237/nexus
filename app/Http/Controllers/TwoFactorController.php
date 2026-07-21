<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    public function __construct(protected TwoFactorAuthenticationService $twoFactor)
    {
    }

    /**
     * Étape 1 : génère un secret temporaire (pas encore confirmé) et
     * l'affiche sous forme de QR code à scanner.
     */
    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();

        $secret = $this->twoFactor->generateSecretKey();

        $request->session()->put('2fa.setup.secret', $secret);

        return redirect()->route('profile.edit', ['show2fa' => 1]);
    }

    /**
     * Étape 2 : l'utilisateur saisit le code affiché par son application
     * pour confirmer qu'il a bien configuré l'appareil. On stocke alors
     * le secret définitivement et on génère les codes de récupération.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $secret = $request->session()->get('2fa.setup.secret');

        if (! $secret) {
            return redirect()->route('profile.edit')->with('error', 'Aucune configuration 2FA en cours.');
        }

        if (! $this->twoFactor->verifyKey($secret, (string) $request->string('code'))) {
            throw ValidationException::withMessages([
                'code' => 'Le code saisi est invalide. Vérifie l\'heure de ton téléphone et réessaie.',
            ]);
        }

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();

        $request->user()->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $this->twoFactor->encryptRecoveryCodes($recoveryCodes),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('2fa.setup.secret');
        $request->session()->put('2fa.setup.recovery_codes', $recoveryCodes);

        return redirect()->route('profile.edit', ['show2fa' => 1])
            ->with('success', 'Authentification à deux facteurs activée avec succès.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return redirect()->route('profile.edit')->with('success', 'Authentification à deux facteurs désactivée.');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        if (! $request->user()->hasTwoFactorEnabled()) {
            return redirect()->route('profile.edit');
        }

        $codes = $this->twoFactor->generateRecoveryCodes();

        $request->user()->forceFill([
            'two_factor_recovery_codes' => $this->twoFactor->encryptRecoveryCodes($codes),
        ])->save();

        $request->session()->put('2fa.setup.recovery_codes', $codes);

        return redirect()->route('profile.edit', ['show2fa' => 1])
            ->with('success', 'Nouveaux codes de récupération générés.');
    }
}

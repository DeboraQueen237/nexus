<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthenticationService
{
    protected Google2FA $engine;

    public function __construct()
    {
        $this->engine = new Google2FA();
    }

    /**
     * Génère un nouveau secret TOTP en clair (à afficher une seule fois
     * lors de l'étape de configuration, jamais stocké tel quel).
     */
    public function generateSecretKey(): string
    {
        return $this->engine->generateSecretKey();
    }

    /**
     * URL otpauth:// utilisée pour générer le QR code, scannable par
     * Google Authenticator, Authy, 1Password, etc.
     */
    public function qrCodeSvg(User $user, string $secret): string
    {
        $qrCodeUrl = $this->engine->getQRCodeUrl(
            config('app.name', 'NEXUS'),
            $user->email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString($qrCodeUrl);
    }

    /**
     * Vérifie un code TOTP à 6 chiffres saisi par l'utilisateur.
     */
    public function verifyKey(string $secret, string $code): bool
    {
        return (bool) $this->engine->verifyKey($secret, $code, 1);
    }

    /**
     * Génère 8 codes de récupération à usage unique (format lisible).
     * Retourne le tableau en clair (à afficher une seule fois) ; c'est
     * l'appelant qui doit les stocker chiffrés via storeRecoveryCodes().
     */
    public function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn () => Str::lower(Str::random(4)) . '-' . Str::lower(Str::random(4)))
            ->all();
    }

    public function encryptRecoveryCodes(array $codes): string
    {
        return Crypt::encryptString(json_encode($codes));
    }

    public function decryptRecoveryCodes(?string $encrypted): array
    {
        if (! $encrypted) {
            return [];
        }

        try {
            return json_decode(Crypt::decryptString($encrypted), true) ?? [];
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * Consomme un code de récupération s'il est valide (usage unique).
     * Retourne true si le code était valide et a été retiré de la liste.
     */
    public function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $this->decryptRecoveryCodes($user->two_factor_recovery_codes);
        $code = Str::lower(trim($code));

        if (! in_array($code, $codes, true)) {
            return false;
        }

        $remaining = array_values(array_diff($codes, [$code]));
        $user->forceFill([
            'two_factor_recovery_codes' => $this->encryptRecoveryCodes($remaining),
        ])->save();

        return true;
    }
}

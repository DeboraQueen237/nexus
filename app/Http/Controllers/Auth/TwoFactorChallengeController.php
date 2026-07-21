<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function __construct(protected TwoFactorAuthenticationService $twoFactor)
    {
    }

    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('2fa.user.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $userId = $request->session()->get('2fa.user.id');
        $user = User::find($userId);

        if (! $user) {
            return redirect()->route('login');
        }

        $throttleKey = '2fa:' . $user->id . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'code' => "Trop de tentatives. Réessayez dans {$seconds} secondes.",
            ]);
        }

        $valid = false;

        if ($request->filled('recovery_code')) {
            $valid = $this->twoFactor->consumeRecoveryCode($user, (string) $request->string('recovery_code'));
        } elseif ($request->filled('code')) {
            $valid = $this->twoFactor->verifyKey($user->two_factor_secret, (string) $request->string('code'));
        }

        if (! $valid) {
            RateLimiter::hit($throttleKey);

            LoginHistory::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'failed_2fa',
                'created_at' => now(),
            ]);

            throw ValidationException::withMessages([
                'code' => 'Le code saisi est invalide.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        $remember = $request->session()->pull('2fa.remember', false);
        $request->session()->forget('2fa.user.id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        app(\App\Http\Controllers\Auth\AuthenticatedSessionController::class)
            ->recordSuccessfulLogin($request, $user);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}

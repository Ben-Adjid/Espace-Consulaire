<?php

namespace App\Filament\Admin\Pages;

use App\Services\TwoFactor;
use Filament\Pages\Page;
use Illuminate\Support\Facades\RateLimiter;

/** Vérification après connexion : code à 6 chiffres de l'application, ou code de secours. */
class TwoFactorChallenge extends Page
{
    use \App\Filament\Concerns\TranslatedPageLabels;

    protected static string $view = 'filament.admin.pages.two-factor-challenge';

    protected static ?string $slug = 'verification-2fa';

    protected static ?string $title = 'Vérification en deux étapes';

    protected static bool $shouldRegisterNavigation = false;

    public string $code = '';

    public bool $useRecovery = false;

    /** Mémoriser ce navigateur jusqu’au prochain renouvellement annuel (le mot de passe reste demandé). */
    public bool $rememberDevice = true;

    public function verify(TwoFactor $twoFactor): void
    {
        $user = auth()->user();
        $key = '2fa:'.$user->getKey();

        // 5 essais par tranche de 5 minutes.
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('code', 'Trop de tentatives. Réessayez dans quelques minutes.');

            return;
        }

        $ok = $this->useRecovery
            ? $twoFactor->consumeRecoveryCode($user, $this->code)
            : $twoFactor->verify((string) $user->two_factor_secret, $this->code);

        if (! $ok) {
            RateLimiter::hit($key, 300);
            $this->addError('code', 'Code incorrect.');

            return;
        }

        RateLimiter::clear($key);
        session()->put('two_factor_passed', $user->getKey());
        if ($this->rememberDevice) {
            \Illuminate\Support\Facades\Cookie::queue(\App\Services\TrustedDevices::remember($user, request()));
        }
        session()->regenerate();

        $this->redirect(filament()->getUrl());
    }
}

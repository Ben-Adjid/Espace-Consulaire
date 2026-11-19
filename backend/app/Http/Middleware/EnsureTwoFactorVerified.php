<?php

namespace App\Http\Middleware;

use App\Filament\Admin\Pages\TwoFactorChallenge;
use App\Filament\Admin\Pages\TwoFactorSettings;
use App\Services\TwoFactor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Panneau d'administration : exige le code de double authentification après chaque connexion
 * (si activée), et impose son activation pour les rôles configurés.
 */
class EnsureTwoFactorVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $route = $request->route()?->getName();
        $challenge = TwoFactorChallenge::getRouteName();
        $settings = TwoFactorSettings::getRouteName();
        $logout = 'filament.admin.auth.logout';

        if ($user->hasTwoFactorEnabled()) {
            // Appareil de confiance valide : pas de nouveau code jusqu’au renouvellement.
            if (session('two_factor_passed') !== $user->getKey() && \App\Services\TrustedDevices::isTrusted($user, $request)) {
                session()->put('two_factor_passed', $user->getKey());
            }
            if (session('two_factor_passed') !== $user->getKey() && ! in_array($route, [$challenge, $logout], true)) {
                return redirect()->to(TwoFactorChallenge::getUrl());
            }

            return $next($request);
        }

        // Rôle soumis à l'obligation : activation imposée avant tout autre écran.
        if (TwoFactor::requiredFor($user) && ! in_array($route, [$settings, $logout], true)) {
            return redirect()->to(TwoFactorSettings::getUrl());
        }

        return $next($request);
    }
}

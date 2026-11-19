<?php

namespace App\Filament\Admin\Pages;

use App\Services\TwoFactor;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Sécurité du compte : activer / désactiver la double authentification (TOTP).
 * Le secret n'est enregistré qu'après confirmation d'un premier code valide.
 */
class TwoFactorSettings extends Page
{
    use \App\Filament\Concerns\TranslatedPageLabels;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static string $view = 'filament.admin.pages.two-factor-settings';

    protected static ?string $slug = 'securite-compte';

    protected static ?string $title = 'Sécurité du compte';

    protected static bool $shouldRegisterNavigation = false;

    /** Secret en cours d'activation (pas encore confirmé). */
    public ?string $pendingSecret = null;

    public string $code = '';

    /** Codes de secours affichés une seule fois, juste après l'activation. */
    public array $freshRecoveryCodes = [];

    public function startEnrollment(TwoFactor $twoFactor): void
    {
        $this->pendingSecret = $twoFactor->generateSecret();
        $this->code = '';
    }

    public function confirmEnrollment(TwoFactor $twoFactor): void
    {
        if (! $this->pendingSecret || ! $twoFactor->verify($this->pendingSecret, $this->code)) {
            $this->addError('code', 'Code incorrect. Vérifiez l’heure de votre téléphone et réessayez.');

            return;
        }

        $user = auth()->user();
        $codes = $twoFactor->recoveryCodes();
        $user->forceFill([
            'two_factor_secret' => $this->pendingSecret,
            'two_factor_recovery_codes' => $codes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        // La session en cours est considérée comme vérifiée.
        session()->put('two_factor_passed', $user->getKey());
        $this->freshRecoveryCodes = $codes;
        $this->pendingSecret = null;
        $this->code = '';

        Notification::make()->success()->title(__('Double authentification activée'))->send();
    }

    /** Oublie tous les appareils de confiance de ce compte (ce navigateur compris). */
    public function forgetDevices(): void
    {
        $count = \App\Services\TrustedDevices::forgetAll(auth()->user());
        \Filament\Notifications\Notification::make()
            ->title($count.' appareil(s) oublié(s). Le code à 6 chiffres sera redemandé sur chacun.')
            ->success()->send();
    }

    public function disable(TwoFactor $twoFactor): void
    {
        $user = auth()->user();
        if (! $user->hasTwoFactorEnabled() || ! $twoFactor->verify($user->two_factor_secret, $this->code)) {
            $this->addError('code', 'Code incorrect : saisissez le code actuel de votre application pour désactiver.');

            return;
        }

        if (TwoFactor::requiredFor($user)) {
            $this->addError('code', 'La double authentification est obligatoire pour votre rôle.');

            return;
        }

        $user->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
        $this->code = '';
        Notification::make()->success()->title(__('Double authentification désactivée'))->send();
    }

    public function qrCode(TwoFactor $twoFactor): ?string
    {
        return $this->pendingSecret ? $twoFactor->qrCodeSvg(auth()->user(), $this->pendingSecret) : null;
    }

    protected function getViewData(): array
    {
        return [
            'enabled' => auth()->user()->hasTwoFactorEnabled(),
            'required' => TwoFactor::requiredFor(auth()->user()),
            'qr' => $this->qrCode(app(TwoFactor::class)),
        ];
    }
}

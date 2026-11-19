<?php

namespace App\Filament\Pages\Auth;

use App\Models\Citizen;
use App\Models\User;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Connexion à l'espace agents (super administrateur, consuls, agents). Le rôle est vérifié côté serveur
 * à chaque requête (User::canAccessPanel) ; le tableau de bord affiché dépend de ce rôle.
 *
 * Messages explicites, seulement si le mot de passe est correct (aucune énumération des comptes) :
 * consulat suspendu ou désactivé, compte de démonstration en production, ou compte citoyen.
 */
class Login extends BaseLogin
{
    public function getHeading(): string|Htmlable
    {
        return __('Espace agents et consuls');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('Connectez-vous avec le compte fourni par votre consulat ou par la plateforme.');
    }

    protected function throwFailureValidationException(): never
    {
        $data = $this->form->getState();
        $email = (string) ($data['email'] ?? '');
        $password = (string) ($data['password'] ?? '');
        $user = User::query()->where('email', $email)->first();
        $consulate = $user?->consulate;

        if ($user && Hash::check($password, $user->password)) {
            if (! $user->isSuperAdmin() && $consulate && ! $consulate->is_active) {
                $message = $consulate->suspended_for_nonpayment
                    ? __('Consulat suspendu pour non-paiement. Contactez la plateforme pour renouveler : l’accès est rétabli dès le paiement enregistré.')
                    : __('Ce consulat est désactivé. Contactez la plateforme pour toute question.');

                throw ValidationException::withMessages(['data.email' => $message]);
            }

            if (app()->isProduction() && $user->isDemoAccount()) {
                throw ValidationException::withMessages(['data.email' => __('Les comptes de démonstration sont désactivés sur la plateforme en service.')]);
            }
        }

        // Un citoyen qui se trompe d'espace : on l'oriente vers l'espace citoyen de son consulat.
        if (! $user) {
            $citizen = Citizen::withoutGlobalScopes()->where('email', mb_strtolower(trim($email)))->first();
            if ($citizen && Hash::check($password, $citizen->password)) {
                throw ValidationException::withMessages([
                    'data.email' => __('Ce compte est un compte citoyen. Connectez-vous depuis « Espace citoyen » sur le site de votre consulat.'),
                ]);
            }
        }

        parent::throwFailureValidationException();
    }
}

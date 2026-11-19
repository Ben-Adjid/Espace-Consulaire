<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Réinitialise la double authentification d'un compte qui a perdu son téléphone ET ses codes de secours.
 * Réservée à l'opérateur qui a accès au serveur : aucune réinitialisation possible depuis le web.
 * Après l'opération, l'utilisateur doit se reconnecter et réactiver la double authentification.
 */
class ResetTwoFactor extends Command
{
    protected $signature = 'consulat:reset-two-factor {email : Email du compte} {--force : Ne pas demander de confirmation}';

    protected $description = 'Désactive la double authentification d’un compte (perte du téléphone et des codes de secours).';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('Aucun compte avec cet email.');

            return self::FAILURE;
        }
        if (! $user->hasTwoFactorEnabled()) {
            $this->info('La double authentification n’est pas active pour ce compte.');

            return self::SUCCESS;
        }
        if (! $this->option('force') && ! $this->confirm("Désactiver la double authentification de {$user->email} ?")) {
            return self::FAILURE;
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        Log::warning('Double authentification réinitialisée par l’opérateur', ['user_id' => $user->id]);
        $this->info('Double authentification désactivée. Reconnexion puis réactivation requises.');

        return self::SUCCESS;
    }
}

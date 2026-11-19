<?php

namespace App\Console\Commands;

use App\Models\DataAccessLog;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Revue trimestrielle des droits : liste les comptes par consulat et par rôle, les comptes inactifs,
 * et le nombre de consultations de données personnelles du trimestre. À relire et signer par le responsable.
 */
class ReviewAccessRights extends Command
{
    protected $signature = 'consulat:review-access-rights {--days=90 : Période de consultation analysée}';

    protected $description = 'Édite la revue trimestrielle des droits d’accès du personnel.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $this->line('REVUE DES DROITS — '.now()->toDateString().' (période : '.$days.' jours)');
        $this->line('');

        User::with('roles')->orderBy('consulate_id')->orderBy('email')->get()->groupBy('consulate_id')->each(function ($users, $consulateId) {
            $this->line('Consulat : '.($consulateId ?: 'plateforme'));
            foreach ($users as $u) {
                $roles = $u->getRoleNames()->implode(', ') ?: 'aucun rôle';
                $state = $u->is_active ? 'actif' : 'INACTIF';
                $twoFactor = $u->hasTwoFactorEnabled() ? '2FA oui' : '2FA non';
                $this->line("  - {$u->email} · {$roles} · {$state} · {$twoFactor}");
            }
        });

        $accesses = DataAccessLog::where('created_at', '>=', now()->subDays($days))->count();
        $this->line('');
        $this->line("Consultations de pièces personnelles sur la période : {$accesses}");
        $this->line('Signature du responsable : ____________________   Date : ____ / ____ / ________');

        return self::SUCCESS;
    }
}

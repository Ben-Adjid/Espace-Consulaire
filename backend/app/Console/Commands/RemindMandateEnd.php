<?php

namespace App\Console\Commands;

use App\Models\AssociationOfficer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/**
 * Rappel de fin de mandat : prévient le membre du bureau dont le mandat se termine dans les 30 jours.
 * Un email par personne et par jour au plus.
 */
class RemindMandateEnd extends Command
{
    protected $signature = 'consulat:remind-mandates {--days=30 : Délai avant la fin du mandat}';

    protected $description = 'Prévient les membres du bureau dont le mandat se termine bientôt (un email par jour au plus).';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $sent = 0;

        $officers = AssociationOfficer::withoutGlobalScopes()
            ->whereNotNull('email')->whereNotNull('mandate_end')
            ->whereBetween('mandate_end', [now()->toDateString(), now()->addDays($days)->toDateString()])
            ->with('association')
            ->get();

        foreach ($officers as $officer) {
            $key = 'mandate-reminder:'.$officer->id.':'.now()->toDateString();
            if (! Cache::add($key, true, now()->endOfDay())) {
                continue;
            }

            $end = $officer->mandate_end->format('d/m/Y');
            Mail::raw(
                "Bonjour {$officer->full_name},\n\nVotre mandat de « ".($officer->association?->name ?? 'l’association')." » se termine le {$end}.\n\n"
                ."Pensez à préparer la suite : élection ou nomination, passation des dossiers et des comptes.",
                fn ($message) => $message->to($officer->email)->subject('Fin de mandat prochaine'),
            );
            $sent++;
        }

        $this->info("Rappels envoyés : {$sent}.");

        return self::SUCCESS;
    }
}

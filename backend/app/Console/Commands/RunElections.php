<?php

namespace App\Console\Commands;

use App\Actions\ProcessElection;
use App\Models\AssociationElection;
use Illuminate\Console\Command;

/**
 * Fait avancer les scrutins en ligne sans intervention humaine : figer l'électorat à l'ouverture,
 * dépouiller à la fermeture, passer au second tour ou proclamer. Lancée toutes les 5 minutes.
 */
class RunElections extends Command
{
    protected $signature = 'consulat:run-elections';

    protected $description = 'Traite automatiquement les scrutins en ligne (électorat, dépouillement, second tour, proclamation).';

    public function handle(ProcessElection $process): int
    {
        $elections = AssociationElection::query()
            ->whereNotNull('voting_opens_at')
            ->whereNotNull('voting_closes_at')
            ->whereNull('proclaimed_at')
            ->where('voting_opens_at', '<=', now())
            ->get();

        foreach ($elections as $election) {
            $outcome = $process($election);
            if ($outcome !== 'waiting') {
                $this->line("Scrutin {$election->year} : {$outcome}");
            }
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Actions\TallyElection;
use App\Actions\VerifyVoteChain;
use App\Models\AssociationElection;
use Illuminate\Console\Command;

/**
 * Produit le « scellé » d'un scrutin : résultats, empreinte finale du journal des votes, et empreinte du scellé lui-même.
 * À publier hors de la plateforme (affichage au siège, envoi à la commission). Toute altération ultérieure
 * de la base ne pourra plus correspondre à ce scellé publié.
 */
class SealElection extends Command
{
    protected $signature = 'consulat:seal-election {election : Identifiant du scrutin} {--file= : Fichier de sortie}';

    protected $description = 'Génère le scellé d’un scrutin (résultats et empreinte du journal) à publier hors plateforme.';

    public function handle(TallyElection $tally, VerifyVoteChain $verify): int
    {
        $election = AssociationElection::with('association')->find($this->argument('election'));
        if (! $election || $election->proclaimed_at === null) {
            $this->error('Scrutin introuvable ou non proclamé : le scellé ne se produit qu’après la proclamation.');

            return self::FAILURE;
        }

        $chain = $verify($election->id);
        $lines = [
            'SCELLÉ DU SCRUTIN',
            'Association : '.$election->association->name,
            'Année : '.$election->year.($election->is_partial ? ' (partiel)' : ''),
            'Proclamé le : '.$election->proclaimed_at->toIso8601String(),
            'Journal valide : '.($chain['valid'] ? 'oui' : 'NON'),
            'Nombre de votes : '.$chain['length'],
            'Empreinte finale du journal : '.($chain['head'] ?? 'aucune'),
            '',
            'RÉSULTATS',
        ];
        foreach ($tally($election) as $round) {
            $lines[] = 'Tour '.$round['round'];
            foreach ($round['roles'] as $role) {
                $lines[] = '  '.$role['label'].' : '.$role['status'].' — '.($role['winner'] ?? '—').' ('.$role['votes'].' / '.$role['turnout'].')';
            }
        }
        $body = implode("\n", $lines)."\n";
        $seal = "Empreinte du scellé (SHA-256) : ".hash('sha256', $body)."\n";

        $file = (string) ($this->option('file') ?: storage_path("app/scelle-scrutin-{$election->id}.txt"));
        file_put_contents($file, $body.$seal);
        $this->info("Scellé écrit : {$file}");
        $this->line($seal);

        return self::SUCCESS;
    }
}

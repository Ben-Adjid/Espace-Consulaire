<?php

namespace App\Actions;

use App\Models\Association;
use App\Models\AssociationCandidate;
use App\Models\AssociationElection;
use App\Models\AssociationElectionResult;
use App\Models\AssociationElectorate;
use App\Models\AssociationMember;
use App\Models\AssociationOfficer;
use App\Models\AssociationVote;
use App\Models\Citizen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Traitement automatique d'un scrutin en ligne, appelé à intervalles réguliers (commande planifiée).
 * Aucune action humaine n'est nécessaire.
 *
 * 1. Ouverture du vote : la liste des électeurs est figée (membres actifs, email confirmé, compte actif).
 * 2. Fermeture, premier tour : par fonction, élu si un candidat a plus de la moitié des voix exprimées pour cette fonction.
 *    Sinon, second tour : les deux premiers (et les ex æquo à la deuxième place) sont qualifiés.
 * 3. Second tour (durée fixée par l'élection) : le plus de voix est élu. Égalité : départage par ancienneté
 *    d'adhésion (la plus ancienne), puis par date de candidature, puis par numéro (déterministe).
 * 4. Proclamation : les élus entrent au bureau, les anciens mandats se terminent la veille.
 *
 * Une fonction sans aucune voix reste vacante et est signalée. Chaque étape est enregistrée dans les résultats.
 */
class ProcessElection
{
    /** @return string frozen | second_round | proclaimed | waiting */
    public function __invoke(AssociationElection $election): string
    {
        return DB::transaction(function () use ($election) {
            $e = AssociationElection::whereKey($election->id)->lockForUpdate()->first();
            if (! $e || ! $e->isOnline() || $e->proclaimed_at !== null) {
                return 'waiting';
            }

            if ($e->electorate_frozen_at === null && now()->gte($e->voting_opens_at)) {
                $this->freezeElectorate($e);

                return 'frozen';
            }

            if ($e->phase() !== 'closed') {
                return 'waiting';
            }

            // Le tour est déjà dépouillé (sécurité si la commande est relancée) : rien à refaire.
            $round = (int) $e->current_round;
            if (AssociationElectionResult::where('election_id', $e->id)->where('round', $round)->exists()) {
                return 'waiting';
            }

            return $this->decideRound($e, $round);
        });
    }

    private function freezeElectorate(AssociationElection $e): void
    {
        // Vote du bureau : l'électorat est le bureau en exercice ; sinon, les membres actifs.
        if ($e->electorate === 'board') {
            $members = AssociationOfficer::where('association_id', $e->association_id)
                ->whereNotNull('citizen_id')
                ->get()
                ->filter(fn (AssociationOfficer $o) => $o->isCurrent())
                ->pluck('citizen_id')
                ->unique();
        } else {
            $members = AssociationMember::where('association_id', $e->association_id)
                ->where('status', 'active')
                ->whereNotNull('citizen_id')
                ->pluck('citizen_id');
        }

        $eligible = Citizen::whereIn('id', $members)->where('status', Citizen::STATUS_ACTIVE)
            ->whereNotNull('email_verified_at')->pluck('id');

        foreach ($eligible as $citizenId) {
            AssociationElectorate::create(['election_id' => $e->id, 'citizen_id' => $citizenId]);
        }

        $e->update(['electorate_frozen_at' => now()]);

        // Convocation : chaque électeur est invité à voter, dans sa langue.
        $association = $e->association;
        foreach (Citizen::whereIn('id', $eligible)->get() as $citizen) {
            if (! $citizen->email) {
                continue;
            }
            try {
                Mail::to($citizen->email)->send(new \App\Mail\ElectionConvocationMail($citizen, (string) $association->name, (int) $e->year, $e->voting_closes_at->format('d/m/Y H:i'), rtrim((string) config('app.frontend_url'), '/').'/'.$association->consulate()->value('slug').'/'.($citizen->locale ?: 'fr').'/compte/elections'));
            } catch (Throwable $exception) {
                Log::warning('Convocation non envoyée', ['election' => $e->id, 'error' => $exception->getMessage()]);
            }
        }
    }

    private function decideRound(AssociationElection $e, int $round): string
    {
        // Fonctions à traiter : toutes au premier tour ; seulement celles en second tour ensuite.
        $roles = $round === 1
            ? AssociationCandidate::where('election_id', $e->id)->where('status', 'approved')->distinct()->pluck('role')->all()
            : AssociationElectionResult::where('election_id', $e->id)->where('round', 1)->where('status', 'second_round')->pluck('role')->all();

        $memberships = AssociationMember::where('association_id', $e->association_id)->pluck('joined_at', 'citizen_id');
        $decisions = [];
        $needsSecondRound = false;

        foreach ($roles as $role) {
            $candidates = AssociationCandidate::where('election_id', $e->id)->where('status', 'approved')->where('role', $role)
                ->when($round > 1, fn ($q) => $q->where('qualified_round2', true))
                ->with('citizen')->get();

            $votes = AssociationVote::where('election_id', $e->id)->where('round', $round)
                ->whereIn('candidate_id', $candidates->pluck('id'))
                ->selectRaw('candidate_id, COUNT(*) as total')->groupBy('candidate_id')->get()->pluck('total', 'candidate_id');

            $turnout = (int) $votes->sum();
            $rows = $candidates->map(fn (AssociationCandidate $c) => [
                'candidate' => $c,
                'votes' => (int) ($votes[$c->id] ?? 0),
                'seniority' => $memberships[$c->citizen_id] ?? null,
            ])->sort(function (array $a, array $b) {
                // Plus de voix d'abord ; à égalité : ancienneté (date d'adhésion la plus ancienne), puis candidature la plus ancienne, puis numéro.
                return [$b['votes'], $a['seniority'] ?? '9999-12-31', $a['candidate']->created_at->timestamp, $a['candidate']->id]
                    <=> [$a['votes'], $b['seniority'] ?? '9999-12-31', $b['candidate']->created_at->timestamp, $b['candidate']->id];
            })->values();

            if ($turnout === 0 || $rows->isEmpty()) {
                $decisions[] = ['role' => $role, 'status' => 'vacant', 'candidate' => null, 'votes' => 0, 'turnout' => 0];

                continue;
            }

            $top = $rows->first();
            if ($round === 1 && $top['votes'] * 2 > $turnout) {
                $decisions[] = ['role' => $role, 'status' => 'elected', 'candidate' => $top['candidate'], 'votes' => $top['votes'], 'turnout' => $turnout];

                continue;
            }

            if ($round === 1) {
                // Pas de majorité absolue : second tour entre les deux premiers, ex æquo à la deuxième place inclus.
                $second = $rows->get(1);
                $qualified = $rows->filter(fn ($r) => $r['candidate']->id === $top['candidate']->id
                    || ($second && $r['votes'] === $second['votes']))->pluck('candidate');
                AssociationCandidate::whereIn('id', $qualified->pluck('id'))->update(['qualified_round2' => true]);
                $decisions[] = ['role' => $role, 'status' => 'second_round', 'candidate' => null, 'votes' => $top['votes'], 'turnout' => $turnout];
                $needsSecondRound = true;

                continue;
            }

            // Second tour : le premier de la liste l'emporte (égalité déjà départagée par le tri).
            $decisions[] = ['role' => $role, 'status' => 'elected', 'candidate' => $top['candidate'], 'votes' => $top['votes'], 'turnout' => $turnout];
        }

        foreach ($decisions as $d) {
            AssociationElectionResult::create([
                'election_id' => $e->id,
                'round' => $round,
                'role' => $d['role'],
                'candidate_id' => $d['candidate']?->id,
                'status' => $d['status'],
                'votes' => $d['votes'],
                'turnout' => $d['turnout'],
                'decided_at' => now(),
            ]);
        }

        if ($needsSecondRound) {
            $e->systemTransition = true; // passage au second tour : autorisé malgré le verrou sur les dates
            $e->update([
                'current_round' => 2,
                'voting_opens_at' => now(),
                'voting_closes_at' => now()->addHours((int) $e->second_round_hours),
            ]);
            $e->systemTransition = false;

            return 'second_round';
        }

        $this->proclaim($e);

        return 'proclaimed';
    }

    private function proclaim(AssociationElection $e): void
    {
        // Le résultat le plus récent de chaque fonction fait foi (le second tour remplace le premier tour).
        $final = AssociationElectionResult::where('election_id', $e->id)->with('candidate.citizen')
            ->orderByDesc('round')->get()->unique('role')->where('status', 'elected');

        $officers = $final->map(fn (AssociationElectionResult $r) => [
            'role' => $r->role,
            'full_name' => (string) $r->candidate->citizen->full_name,
            'citizen_id' => $r->candidate->citizen_id,
        ])->values()->all();

        // Postes vacants : tout poste de la portée de l'élection qui n'a pas d'élu, qu'il ait eu des candidats
        // sans voix (« vacant ») ou aucun candidat du tout. Sinon le poste disparaîtrait sans successeur.
        $scope = $e->is_partial
            ? array_values((array) ($e->scope_roles ?? []))
            : array_keys(\App\Models\Association::ROLES);
        $vacant = array_values(array_unique(array_merge(
            AssociationElectionResult::where('election_id', $e->id)->orderByDesc('round')->get()
                ->unique('role')->where('status', 'vacant')->pluck('role')->values()->all(),
            array_diff($scope, $final->pluck('role')->all()),
        )));

        $heldOn = now()->toDateString();
        $e->update(['held_on' => $heldOn, 'proclaimed_at' => now(), 'processed_at' => now()]);

        app(InstallBoard::class)($e->association, $e->refresh(), $heldOn, $officers);

        // Postes restés vacants : élection partielle ouverte aux membres ; si le poste reste vacant, vote du bureau.
        if ($vacant !== []) {
            if (! $e->is_partial) {
                app(CreatePartialElection::class)($e->refresh(), $vacant, 'members');
            } elseif ($e->electorate === 'members') {
                app(CreatePartialElection::class)($e->refresh(), $vacant, 'board');
            }
        }
    }
}

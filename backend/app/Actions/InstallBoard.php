<?php

namespace App\Actions;

use App\Mail\AssociationRoleMail;
use App\Models\Association;
use App\Models\AssociationElection;
use App\Models\AssociationOfficer;
use App\Models\Citizen;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Installe un bureau élu : les mandats précédents se terminent la veille, le nouveau bureau prend ses mandats
 * à la date de l'élection, les adhésions liées aux mandats finis sont terminées, et les personnes sont prévenues.
 * Partagé par l'élection enregistrée par l'admin et par la proclamation d'un scrutin en ligne.
 */
class InstallBoard
{
    /** @param  array<int, array{role: string, full_name: string, school?: ?string, citizen_id?: ?int}>  $officers */
    public function __invoke(Association $association, AssociationElection $election, string $heldOn, array $officers): void
    {
        $endsOn = CarbonImmutable::parse($heldOn)->subDay()->toDateString();

        // Un bureau saisi à la main n'a pas d'élection : « election_id != ... » seul l'exclurait (NULL).
        $endedMandates = AssociationOfficer::with('citizen')
            ->where('association_id', $association->id)
            ->where(fn ($q) => $q->whereNull('mandate_end')->orWhere('mandate_end', '>=', $heldOn))
            ->where(fn ($q) => $q->whereNull('election_id')->orWhere('election_id', '!=', $election->id))
            // Élection partielle : seules les fonctions visées sont remplacées, le reste du bureau reste en place.
            ->when($election->scope_roles !== null, fn ($q) => $q->whereIn('role', $election->scope_roles))
            ->get()
            ->all();

        DB::transaction(function () use ($association, $election, $heldOn, $officers, $endsOn, $endedMandates) {
            AssociationOfficer::whereIn('id', array_map(fn ($o) => $o->id, $endedMandates))
                ->update(['mandate_end' => $endsOn]);

            foreach (array_values($officers) as $i => $officer) {
                AssociationOfficer::create([
                    'association_id' => $association->id,
                    'election_id' => $election->id,
                    'citizen_id' => $officer['citizen_id'] ?? null,
                    'role' => $officer['role'],
                    'full_name' => $officer['full_name'],
                    'school' => $officer['school'] ?? null,
                    'mandate_start' => $heldOn,
                    'mandate_end' => null,
                    'sort_order' => $i,
                ]);
            }
        });

        app(EndMemberships::class)($association);

        $this->notify($association, $officers, $endedMandates, $endsOn);
    }

    /** Nouveaux membres du bureau, et anciens membres dont le mandat prend fin, s'ils ont un compte citoyen. */
    private function notify(Association $association, array $officers, array $endedMandates, string $endsOn): void
    {
        foreach ($officers as $officer) {
            $citizen = isset($officer['citizen_id']) ? Citizen::find($officer['citizen_id']) : null;
            if ($citizen?->email) {
                Mail::to($citizen->email)->send(new AssociationRoleMail(
                    $citizen,
                    'appointed',
                    $association->name,
                    Association::ROLES[$officer['role']] ?? $officer['role'],
                ));
            }
        }

        foreach ($endedMandates as $old) {
            if ($old->citizen?->email) {
                Mail::to($old->citizen->email)->send(new AssociationRoleMail(
                    $old->citizen,
                    'ended',
                    $association->name,
                    Association::ROLES[$old->role] ?? $old->role,
                    $endsOn,
                ));
            }
        }
    }
}

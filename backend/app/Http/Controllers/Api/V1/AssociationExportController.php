<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\TallyElection;
use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\AssociationActivity;
use App\Models\AssociationElection;
use App\Models\AssociationLedgerEntry;
use App\Services\ConsulateContext;
use Illuminate\Http\Response;

/**
 * Export public au format CSV (ouverture directe dans Excel) : registre financier approuvé, activités publiées,
 * résultats des scrutins publiés. Aucune donnée personnelle sur les adhérents : ni liste de membres, ni contacts.
 */
class AssociationExportController extends Controller
{
    public function __invoke(ConsulateContext $context): Response
    {
        $consulate = $context->require();
        $association = Association::withoutGlobalScopes()->where('consulate_id', $consulate->id)->where('is_public', true)->first();
        if (! $association) {
            abort(404);
        }

        $rows = [];
        $rows[] = ['Section', 'Date', 'Type', 'Libellé', 'Montant (FCFA)'];
        foreach (AssociationLedgerEntry::where('association_id', $association->id)->where('status', 'approved')->orderBy('occurred_on')->orderBy('id')->get() as $e) {
            $rows[] = ['Registre financier', $e->occurred_on->toDateString(), $e->kind === 'expense' ? 'Dépense' : 'Cotisation', $e->label, $e->amount];
        }
        foreach (AssociationActivity::where('association_id', $association->id)->where('is_public', true)->orderBy('starts_on')->get() as $a) {
            $rows[] = ['Activité', $a->starts_on->toDateString(), '', $a->title, ''];
        }
        foreach (AssociationElection::where('association_id', $association->id)->whereNotNull('proclaimed_at')->orderBy('year')->get() as $election) {
            foreach (app(TallyElection::class)($election) as $round) {
                foreach ($round['roles'] as $role) {
                    $rows[] = ['Résultat '.$election->year, $election->held_on->toDateString(), 'Tour '.$round['round'], $role['label'].' : '.($role['winner'] ?? $role['status']), $role['votes']];
                }
            }
        }

        $csv = "\xEF\xBB\xBF".collect($rows)->map(fn (array $r) => collect($r)->map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"')->implode(';'))->implode("\r\n")."\r\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="association-'.$consulate->slug.'.csv"',
        ]);
    }
}

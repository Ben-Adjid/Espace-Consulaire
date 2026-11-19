<?php

namespace App\Actions;

use App\Mail\PreRequestDecisionMail;
use App\Models\PreRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Décision du consulat sur une pré-demande soumise : valider, renvoyer pour complément, ou refuser,
 * avec une décision pour chaque pièce (acceptée / refusée). Règles vérifiées côté serveur :
 * - seule une pré-demande « soumise » peut être examinée ;
 * - un motif est obligatoire pour un renvoi, un refus, et pour chaque pièce refusée ;
 * - une pré-demande ne peut pas être validée si une de ses pièces est refusée ;
 * - valider la pré-demande accepte les pièces restées « à vérifier » ;
 * - seules les pièces de cette pré-demande peuvent être décidées.
 * Le citoyen est prévenu par email, avec la liste des pièces refusées.
 */
class ReviewPreRequest
{
    public const DECISIONS = ['validated', 'returned', 'rejected'];

    /**
     * @param  array<int|string, array{status?: ?string, note?: ?string}>  $documents  décisions par identifiant de pièce
     */
    public function __invoke(PreRequest $request, string $decision, ?string $note, User $reviewer, array $documents = []): PreRequest
    {
        if (! in_array($decision, self::DECISIONS, true)) {
            throw ValidationException::withMessages(['decision' => 'Décision inconnue.']);
        }

        if ($request->status !== 'submitted') {
            throw ValidationException::withMessages(['decision' => 'Cette pré-demande a déjà été examinée ou n’a pas encore été soumise.']);
        }

        $note = trim((string) $note) ?: null;
        if ($decision !== 'validated' && $note === null) {
            throw ValidationException::withMessages(['note' => 'Un motif est obligatoire pour un renvoi ou un refus.']);
        }

        $docs = $request->documents()->get()->keyBy('id');
        $changes = [];
        foreach ($documents as $id => $review) {
            $doc = $docs->get((int) $id);
            if (! $doc) {
                throw ValidationException::withMessages(['documents' => 'Une des pièces n’appartient pas à cette pré-demande.']);
            }
            $status = $review['status'] ?? null;
            if ($status === null || $status === '' || $status === 'pending') {
                continue;
            }
            if (! in_array($status, ['accepted', 'rejected'], true)) {
                throw ValidationException::withMessages(['documents' => 'Décision de pièce inconnue.']);
            }
            $docNote = trim((string) ($review['note'] ?? '')) ?: null;
            if ($status === 'rejected' && $docNote === null) {
                throw ValidationException::withMessages(['documents' => 'Indiquez le motif du refus de la pièce « '.$doc->label.' ».']);
            }
            $changes[$doc->id] = ['status' => $status, 'review_note' => $status === 'rejected' ? $docNote : null];
        }

        $rejectedAfter = $docs->filter(fn ($d) => ($changes[$d->id]['status'] ?? $d->status) === 'rejected');
        if ($decision === 'validated' && $rejectedAfter->isNotEmpty()) {
            throw ValidationException::withMessages(['decision' => 'Une pièce est refusée : renvoyez la pré-demande pour complément au lieu de la valider.']);
        }

        DB::transaction(function () use ($request, $decision, $note, $reviewer, $docs, $changes) {
            foreach ($changes as $id => $values) {
                $docs[$id]->update($values);
            }
            if ($decision === 'validated') {
                // Validation : les pièces encore « à vérifier » sont acceptées.
                $request->documents()->where('status', 'pending')->update(['status' => 'accepted', 'review_note' => null]);
            }
            $request->update([
                'status' => $decision,
                'staff_note' => $note,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            // Historique : décision, motif et pièces refusées.
            $refused = collect($changes)->filter(fn ($v) => $v['status'] === 'rejected')->keys()
                ->map(fn ($id) => $docs[$id]->label.' ('.$changes[$id]['review_note'].')');
            $summary = __(PreRequest::STATUSES[$decision]).($note ? ' — '.$note : '')
                .($refused->isNotEmpty() ? ' — '.__('Pièces refusées :').' '.$refused->implode(', ') : '');
            $request->recordEvent('reviewed', $summary, $reviewer);
        });

        $citizen = $request->citizen()->withoutGlobalScopes()->first();
        if ($citizen?->email) {
            Mail::to($citizen->email)->send(new PreRequestDecisionMail($request->load(['service', 'documents']), $citizen));
        }

        return $request;
    }
}

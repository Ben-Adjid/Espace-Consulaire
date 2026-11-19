<?php

namespace App\Actions;

use App\Models\PreRequest;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Attribue une pré-demande à un membre de l'équipe du consulat (ou la libère).
 * Règles : dossier encore en attente de décision ; destinataire actif, du même consulat, agent ou consul.
 * Un agent ne peut se l'attribuer qu'à lui-même ; le consul (gestion de l'équipe) peut l'attribuer à tout membre.
 */
class AssignPreRequest
{
    public function __invoke(PreRequest $request, ?User $assignee, User $by): PreRequest
    {
        if ($request->status !== 'submitted') {
            throw ValidationException::withMessages(['assigned_to' => __('Seul un dossier en attente de décision peut être attribué.')]);
        }

        $canManageTeam = $by->isSuperAdmin() || $by->can('manage_users');
        if (! $canManageTeam && $assignee?->id !== $by->id) {
            throw ValidationException::withMessages(['assigned_to' => __('Vous ne pouvez attribuer un dossier qu’à vous-même.')]);
        }

        if ($assignee) {
            $sameConsulate = (int) $assignee->consulate_id === (int) $request->consulate_id;
            $eligible = $assignee->is_active && $assignee->hasAnyRole([User::ROLE_CONSULATE_ADMIN, User::ROLE_STAFF]);
            if (! $sameConsulate || ! $eligible) {
                throw ValidationException::withMessages(['assigned_to' => __('Ce membre ne fait pas partie de l’équipe active du consulat.')]);
            }
        }

        $request->update(['assigned_to' => $assignee?->id, 'assigned_at' => $assignee ? now() : null]);
        $request->recordEvent('assigned', $assignee ? __('Attribué à :name', ['name' => $assignee->name]) : __('Dossier libéré'), $by);

        return $request;
    }
}

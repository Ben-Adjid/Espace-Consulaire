<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Candidature d'un membre actif à une fonction, pour une élection en ligne. Validée par le consulat. */
class AssociationCandidate extends Model
{
    public const STATUSES = ['pending' => 'En attente', 'approved' => 'Validée', 'rejected' => 'Refusée'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'qualified_round2' => 'boolean',
            'document_checked_at' => 'datetime',
        ];
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(AssociationElection::class, 'election_id');
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }
}

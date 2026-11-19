<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Conditions d'une fonction du bureau, fixées par l'association (ses statuts). Sans règle enregistrée,
 * aucune condition n'est imposée pour cette fonction.
 */
class AssociationPostRule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'min_membership_months' => 'integer',
            'requires_student' => 'boolean',
            'requires_document' => 'boolean',
            'max_consecutive_terms' => 'integer',
        ];
    }

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }
}

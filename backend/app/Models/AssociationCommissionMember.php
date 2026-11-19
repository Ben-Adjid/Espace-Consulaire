<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Membre de la commission électorale : un citoyen, hors personnel du consulat. Atteste les résultats. */
class AssociationCommissionMember extends Model
{
    public const MAX_MEMBERS = 3;

    protected $guarded = [];

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }
}

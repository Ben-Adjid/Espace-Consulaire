<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Personne à charge d'un compte citoyen (enfant, conjoint, parent…). Les rendez-vous se prennent « pour le compte de ». */
class Dependent extends Model
{
    public const RELATIONS = ['child', 'spouse', 'parent', 'mandate', 'other'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class)->withoutGlobalScopes();
    }
}

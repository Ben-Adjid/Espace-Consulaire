<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Membre inscrit au registre de l'association (étudiant ou stagiaire). Jamais publié. */
class AssociationMember extends Model
{
    public const CATEGORIES = ['student' => 'Étudiant(e)', 'intern' => 'Stagiaire', 'alumni' => 'Ancien(ne)'];

    public const STATUSES = ['active' => 'Actif', 'inactive' => 'Inactif'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['joined_at' => 'date'];
    }

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }
}

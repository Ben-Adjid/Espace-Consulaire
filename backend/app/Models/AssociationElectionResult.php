<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Résultat d'une fonction à un tour : élu, second tour ou vacant. Écrit par le système uniquement. */
class AssociationElectionResult extends Model
{
    public $timestamps = false;

    public const STATUSES = ['elected' => 'Élu(e)', 'second_round' => 'Second tour', 'vacant' => 'Vacant'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(AssociationCandidate::class, 'candidate_id');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Un résultat publié ne peut pas être modifié.'));
        static::deleting(fn () => throw new \LogicException('Un résultat publié ne peut pas être supprimé.'));
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un vote pour un candidat, à un tour donné. Ne contient jamais l'électeur. Non modifiable. */
class AssociationVote extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(AssociationCandidate::class, 'candidate_id');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Un vote ne peut pas être modifié.'));
        static::deleting(fn () => throw new \LogicException('Un vote ne peut pas être supprimé.'));
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Trace qu'un électeur a voté à un tour donné (sans son choix). Non modifiable : un bulletin ne se corrige pas.
 * Le choix est dans AssociationVote, sans lien vers l'électeur.
 */
class AssociationBallot extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['cast_at' => 'datetime', 'round' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Un bulletin ne peut pas être modifié.'));
        static::deleting(fn () => throw new \LogicException('Un bulletin ne peut pas être supprimé.'));
    }
}

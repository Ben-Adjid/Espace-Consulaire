<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Journal des votes chaîné : chaque ligne contient l'empreinte de la précédente. Toute altération du passé se voit.
 * Ne contient que le tour et les candidats choisis, jamais l'électeur.
 */
class AssociationVoteChain extends Model
{
    public $timestamps = false;

    protected $table = 'association_vote_chain';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Le journal des votes est en lecture seule.'));
        static::deleting(fn () => throw new \LogicException('Le journal des votes est en lecture seule.'));
    }
}

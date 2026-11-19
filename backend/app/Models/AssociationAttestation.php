<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Attestation d'un membre de la commission : « j'ai recalculé le résultat, il correspond à ce journal ». Non modifiable. */
class AssociationAttestation extends Model
{
    public $timestamps = false;

    protected $table = 'association_election_attestations';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['signed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Une attestation ne peut pas être modifiée.'));
        static::deleting(fn () => throw new \LogicException('Une attestation ne peut pas être supprimée.'));
    }
}

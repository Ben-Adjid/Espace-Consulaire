<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Élection annuelle : hors ligne (le bureau est saisi) ou en ligne (candidatures, vote à un ou deux tours,
 * dépouillement et proclamation automatiques).
 */
class AssociationElection extends Model
{
    protected $guarded = [];

    /** Champs qui ne peuvent plus changer une fois le vote ouvert : ils fixent les règles du scrutin. */
    private const LOCKED_ONCE_OPEN = ['voting_opens_at', 'voting_closes_at', 'second_round_hours'];

    protected function casts(): array
    {
        return [
            'held_on' => 'date',
            'year' => 'integer',
            'voting_opens_at' => 'datetime',
            'voting_closes_at' => 'datetime',
            'proclaimed_at' => 'datetime',
            'electorate_frozen_at' => 'datetime',
            'processed_at' => 'datetime',
            'second_round_hours' => 'integer',
            'current_round' => 'integer',
            'is_partial' => 'boolean',
            'scope_roles' => 'array',
        ];
    }

    /** Vrai uniquement lors d'un passage automatique (second tour) : le verrou sur les dates ne s'applique pas. */
    public bool $systemTransition = false;

    protected static function booted(): void
    {
        static::updating(function (self $election) {
            if ($election->systemTransition || ! $election->isOnline() || $election->phase() === 'candidacy') {
                return;
            }
            if (count(array_intersect(array_keys($election->getDirty()), self::LOCKED_ONCE_OPEN)) > 0) {
                throw new \LogicException('Les dates et la durée du second tour ne peuvent plus être modifiées une fois le vote ouvert.');
            }
        });
    }

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }

    public function officers(): HasMany
    {
        return $this->hasMany(AssociationOfficer::class, 'election_id');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(AssociationCandidate::class, 'election_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(AssociationElectionResult::class, 'election_id');
    }

    /** Scrutin en ligne configuré (fenêtre de vote renseignée). */
    public function isOnline(): bool
    {
        return $this->voting_opens_at !== null && $this->voting_closes_at !== null;
    }

    /**
     * Phase du scrutin : candidature (avant l'ouverture), vote (ouvert), clos (fermé, en attente du traitement),
     * proclamé. Null pour une élection hors ligne.
     */
    public function phase(): ?string
    {
        if (! $this->isOnline()) {
            return null;
        }
        if ($this->proclaimed_at) {
            return 'proclaimed';
        }

        $now = now();
        if ($now->lt($this->voting_opens_at)) {
            return 'candidacy';
        }

        return $now->lt($this->voting_closes_at) ? 'voting' : 'closed';
    }
}

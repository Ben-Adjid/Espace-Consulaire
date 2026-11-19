<?php

namespace App\Models;

use App\Models\Concerns\BelongsToConsulate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/** Association des étudiants et stagiaires tchadiens rattachée à un consulat (registre tenu par le consulat). */
class Association extends Model
{
    use BelongsToConsulate;

    public const ROLES = [
        'president' => 'Président(e)',
        'vice_president' => 'Vice-président(e)',
        'secretary' => 'Secrétaire général(e)',
        'treasurer' => 'Trésorier(ère)',
        'sports' => 'Responsable des activités (sport, culture)',
        'communication' => 'Responsable de la communication',
        'external_relations' => 'Responsable des relations extérieures',
        'other' => 'Membre du bureau',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_recognized' => 'boolean',
            'is_public' => 'boolean',
            'last_board_closed_at' => 'datetime',
            'recognized_at' => 'date',
        ];
    }

    public function officers(): HasMany
    {
        return $this->hasMany(AssociationOfficer::class)->orderBy('sort_order');
    }

    public function members(): HasMany
    {
        return $this->hasMany(AssociationMember::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(AssociationActivity::class)->orderByDesc('starts_on');
    }

    public function archives(): HasMany
    {
        return $this->hasMany(AssociationArchive::class)->orderBy('person_name');
    }

    public function elections(): HasMany
    {
        return $this->hasMany(AssociationElection::class)->orderByDesc('year');
    }

    public function commissionMembers(): HasMany
    {
        return $this->hasMany(AssociationCommissionMember::class);
    }

    public function postRules(): HasMany
    {
        return $this->hasMany(AssociationPostRule::class)->orderBy('role');
    }

    /** Candidatures à toutes les élections de l'association. */
    public function candidates(): HasManyThrough
    {
        return $this->hasManyThrough(AssociationCandidate::class, AssociationElection::class, 'association_id', 'election_id');
    }
}

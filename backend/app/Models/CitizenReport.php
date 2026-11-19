<?php

namespace App\Models;

use App\Models\Concerns\BelongsToConsulate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Signalement d'un citoyen. La catégorie « integrity » (fraude, corruption) est réservée au super admin :
 * le personnel du consulat concerné ne la voit jamais.
 */
class CitizenReport extends Model
{
    use BelongsToConsulate;

    public const CATEGORY_INTEGRITY = 'integrity';

    public const CATEGORIES = [
        'reception' => 'Accueil au guichet',
        'delay' => 'Délais de traitement',
        'information' => 'Information erronée ou manquante',
        'website' => 'Problème sur le site',
        self::CATEGORY_INTEGRITY => 'Intégrité (fraude, corruption, demande d’argent)',
        'other' => 'Autre',
    ];

    public const STATUSES = [
        'new' => 'Nouveau',
        'in_progress' => 'En cours',
        'resolved' => 'Résolu',
        'closed' => 'Clos',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['responded_at' => 'datetime'];
    }

    /** Ce que le personnel connecté a le droit de voir. */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user?->isSuperAdmin()) {
            return $query;
        }

        return $query->where('category', '!=', self::CATEGORY_INTEGRITY);
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class)->withoutGlobalScopes();
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by')->withoutGlobalScopes();
    }
}

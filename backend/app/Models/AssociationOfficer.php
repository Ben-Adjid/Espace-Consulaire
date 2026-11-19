<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Membre du bureau de l'association, avec son mandat. Peut être rattaché à un compte citoyen. */
class AssociationOfficer extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'mandate_start' => 'date',
            'mandate_end' => 'date',
        ];
    }

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    /** Mandat en cours (sans date de fin, ou fin non dépassée). */
    public function isCurrent(): bool
    {
        return $this->mandate_end === null || $this->mandate_end->isFuture() || $this->mandate_end->isToday();
    }
}

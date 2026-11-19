<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Activité de l'association (événement, atelier, sortie). */
class AssociationActivity extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'is_public' => 'boolean'];
    }

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }
}

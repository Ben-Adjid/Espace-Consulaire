<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Archive : personnalité marquante ayant un lien avec l'association. */
class AssociationArchive extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }
}

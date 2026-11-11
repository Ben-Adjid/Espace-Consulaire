<?php

namespace App\Models\Concerns;

use App\Models\Consulate;
use App\Models\Scopes\ConsulateScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** À utiliser sur tout modèle de contenu portant `consulate_id`. */
trait BelongsToConsulate
{
    protected static function bootBelongsToConsulate(): void
    {
        static::addGlobalScope(new ConsulateScope());

        // Un utilisateur rattaché à un consulat crée toujours dans son consulat.
        static::creating(function ($model) {
            $user = auth()->user();

            if ($user && $user->consulate_id && ! $user->isSuperAdmin()) {
                $model->consulate_id = $user->consulate_id;
            }
        });
    }

    public function consulate(): BelongsTo
    {
        return $this->belongsTo(Consulate::class)->withoutGlobalScopes();
    }
}

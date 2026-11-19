<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Une action du bureau. Le journal ne se modifie ni ne se supprime : il sert de preuve. */
class AssociationBoardLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Le journal du bureau ne peut pas être modifié.'));
        static::deleting(fn () => throw new \LogicException('Le journal du bureau ne peut pas être supprimé.'));
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }
}

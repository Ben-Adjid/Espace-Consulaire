<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Journal de consultation des données personnelles (pièces d'identité, dossiers).
 * Une ligne par consultation : qui, quoi, quand, d'où. Non modifiable.
 */
class DataAccessLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public static function record(?User $user, string $subjectType, string $subjectId, string $action, ?int $consulateId, ?string $ip): void
    {
        static::create([
            'user_id' => $user?->getKey(),
            'consulate_id' => $consulateId,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'action' => $action,
            'ip' => $ip,
            'created_at' => now(),
        ]);
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Le journal d’accès est en lecture seule.'));
        static::deleting(fn () => throw new \LogicException('Le journal d’accès est en lecture seule.'));
    }
}

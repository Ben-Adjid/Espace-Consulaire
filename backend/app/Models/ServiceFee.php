<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tarif d'un service à une date d'effet, avec sa base légale. Un tarif enregistré ne se modifie pas. */
class ServiceFee extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'effective_from' => 'date'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Un tarif enregistré ne se modifie pas : créez un nouveau tarif à sa date d’effet.'));
        static::deleting(fn () => throw new \LogicException('Un tarif enregistré ne se supprime pas : il fait partie de l’historique.'));
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withoutGlobalScopes();
    }

    /** Libellé public : « 60 000 XOF (depuis le 01/01/2026 · Arrêté n° … ) ». */
    public function label(): string
    {
        return number_format((float) $this->amount, 0, ',', ' ').' '.$this->currency;
    }
}

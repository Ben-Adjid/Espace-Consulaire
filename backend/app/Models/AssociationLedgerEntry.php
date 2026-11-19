<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Écriture du registre financier. Une écriture approuvée est figée : elle ne se modifie ni ne se supprime. */
class AssociationLedgerEntry extends Model
{
    public const KINDS = ['contribution' => 'Cotisation', 'expense' => 'Dépense'];

    protected $table = 'association_ledger';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['occurred_on' => 'date', 'approved_at' => 'datetime', 'amount' => 'integer'];
    }

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(Citizen::class, 'proposed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Citizen::class, 'approved_by');
    }

    protected static function booted(): void
    {
        static::updating(function (self $entry) {
            if ($entry->getOriginal('status') === 'approved') {
                throw new \LogicException('Une écriture approuvée ne peut plus être modifiée.');
            }
        });
        static::deleting(function (self $entry) {
            if ($entry->status === 'approved') {
                throw new \LogicException('Une écriture approuvée ne peut pas être supprimée.');
            }
        });
    }
}

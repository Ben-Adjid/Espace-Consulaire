<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ligne de l'historique d'une pré-demande : qui a fait quoi, et quand. */
class PreRequestEvent extends Model
{
    protected $guarded = [];

    public function preRequest(): BelongsTo
    {
        return $this->belongsTo(PreRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return __(PreRequest::EVENTS[$this->type] ?? $this->type);
    }
}

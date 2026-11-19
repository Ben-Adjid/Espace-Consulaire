<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Pièce téléversée : stockée hors du web, nom aléatoire, empreinte SHA-256 pour l'intégrité. */
class RequestDocument extends Model
{
    public const DISK = 'documents';

    public const MAX_KB = 5120;

    public const ALLOWED_MIME = ['application/pdf', 'image/jpeg', 'image/png'];

    /** Examen pièce par pièce par le consulat. */
    public const STATUSES = ['pending' => 'À vérifier', 'accepted' => 'Acceptée', 'rejected' => 'Refusée'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['expires_on' => 'date', 'expiry_reminded_at' => 'datetime'];
    }

    /** Alerte d'expiration : « expired », « soon » (dans les 60 jours) ou null. */
    public function expiryState(): ?string
    {
        if ($this->expires_on === null) {
            return null;
        }

        return $this->expires_on->lt(today()) ? 'expired' : ($this->expires_on->lte(today()->addDays(60)) ? 'soon' : null);
    }

    public function preRequest(): BelongsTo
    {
        return $this->belongsTo(PreRequest::class);
    }
}

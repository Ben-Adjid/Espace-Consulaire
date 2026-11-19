<?php

namespace App\Models;

use App\Models\Concerns\BelongsToConsulate;
use Illuminate\Database\Eloquent\Model;

/** Modèle de réponse du consulat (motif de refus, complément demandé…), réutilisé lors de l'examen des pré-demandes. */
class ResponseTemplate extends Model
{
    use BelongsToConsulate;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}

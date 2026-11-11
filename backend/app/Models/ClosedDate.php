<?php

namespace App\Models;

use App\Models\Concerns\BelongsToConsulate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Jour de fermeture exceptionnelle d'un consulat (jour férié, travaux…). */
class ClosedDate extends Model
{
    use BelongsToConsulate, HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}

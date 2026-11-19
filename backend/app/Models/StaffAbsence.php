<?php

namespace App\Models;

use App\Models\Concerns\BelongsToConsulate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Absence d'un membre de l'équipe (congé, mission, formation) : visible dans le calendrier des rendez-vous. */
class StaffAbsence extends Model
{
    use BelongsToConsulate;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Absences qui couvrent au moins un jour de la période. */
    public function scopeOverlapping(Builder $query, string $from, string $to): Builder
    {
        return $query->whereDate('starts_on', '<=', $to)->whereDate('ends_on', '>=', $from);
    }
}

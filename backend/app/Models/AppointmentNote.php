<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Note interne sur un dossier : visible du personnel du consulat uniquement. */
class AppointmentNote extends Model
{
    protected $guarded = [];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class)->withoutGlobalScopes();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id')->withoutGlobalScopes();
    }
}

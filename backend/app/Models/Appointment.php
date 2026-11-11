<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Models\Concerns\BelongsToConsulate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Appointment extends Model
{
    use BelongsToConsulate, HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'status' => AppointmentStatus::class,
            'confirmed_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withoutGlobalScopes();
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class)->withoutGlobalScopes();
    }

    /** Référence unique lisible, ex. TCH-SN-20261215-K7M2QX. */
    public static function generateReference(string $prefix, string $date): string
    {
        do {
            // Alphabet sans caractères ambigus (0/O, 1/I)
            $suffix = strtr(Str::upper(Str::random(6)), ['0' => 'K', 'O' => 'M', '1' => 'T', 'I' => 'P']);
            $reference = sprintf('%s-%s-%s', $prefix, str_replace('-', '', $date), $suffix);
        } while (static::withoutGlobalScopes()->where('reference', $reference)->exists());

        return $reference;
    }
}

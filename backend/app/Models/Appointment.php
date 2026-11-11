<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Models\Concerns\BelongsToConsulate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Appointment extends Model
{
    use BelongsToConsulate, HasFactory;

    protected $guarded = [];

    /** Jeton en clair, présent uniquement juste après la création (jamais stocké). */
    public ?string $plainAccessToken = null;

    /** @var array<int,string> Champs jamais renvoyés par l'API. */
    protected $hidden = ['access_token_hash', 'notes'];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'status' => AppointmentStatus::class,
            'confirmed_at' => 'datetime',
            'consent_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    /** Référence unique lisible, ex. TCH-SN-20261215-K7M2QX (alphabet sans 0/O/1/I). */
    public static function generateReference(string $prefix, string $date): string
    {
        do {
            $suffix = strtr(Str::upper(Str::random(6)), ['0' => 'K', 'O' => 'M', '1' => 'T', 'I' => 'P']);
            $reference = sprintf('%s-%s-%s', $prefix, str_replace('-', '', $date), $suffix);
        } while (static::withoutGlobalScopes()->where('reference', $reference)->exists());

        return $reference;
    }

    /** Génère le jeton d'accès (confirmation / annulation). Seul le hash est stocké. */
    public function issueAccessToken(): string
    {
        $token = Str::random(48);
        $this->forceFill(['access_token_hash' => hash('sha256', $token)])->save();
        $this->plainAccessToken = $token;

        return $token;
    }

    public function checkAccessToken(?string $token): bool
    {
        if (! $token || ! $this->access_token_hash) {
            return false;
        }

        return hash_equals($this->access_token_hash, hash('sha256', $token));
    }

    /** Annulation possible si le rendez-vous est actif et que le délai minimal est respecté. */
    public function isCancellable(): bool
    {
        if (! in_array($this->status, [AppointmentStatus::Pending, AppointmentStatus::Confirmed], true)) {
            return false;
        }

        $start = Carbon::parse($this->appointment_date->toDateString().' '.$this->start_time);

        return now()->diffInHours($start, false) >= config('booking.cancel_min_hours');
    }
}

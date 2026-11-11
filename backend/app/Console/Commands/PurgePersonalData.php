<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ContactMessage;
use Illuminate\Console\Command;

/**
 * Purge RGPD : supprime les données personnelles au-delà de la durée de conservation.
 * - rendez-vous passés, annulés ou terminés (nom, email, téléphone, notes) ;
 * - messages de contact anciens.
 * Les rendez-vous à venir ne sont jamais touchés.
 */
class PurgePersonalData extends Command
{
    protected $signature = 'consulat:purge-personal-data {--days= : Durée de conservation (défaut : DATA_RETENTION_DAYS)}';

    protected $description = 'Supprime les données personnelles expirées (RGPD)';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('booking.retention_days'));
        $cutoff = now()->subDays($days);

        $appointments = Appointment::withoutGlobalScopes()
            ->where(function ($q) use ($cutoff) {
                $q->where('appointment_date', '<', $cutoff->toDateString())
                    ->orWhereIn('status', [AppointmentStatus::Cancelled->value]);
            })
            ->where('appointment_date', '<', now()->subDays(1)->toDateString())
            ->delete();

        $messages = ContactMessage::withoutGlobalScopes()->where('created_at', '<', $cutoff)->delete();

        $this->info("Purge : {$appointments} rendez-vous et {$messages} message(s) supprimés (plus de {$days} jours).");

        return self::SUCCESS;
    }
}

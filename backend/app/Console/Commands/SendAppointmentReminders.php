<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Mail\AppointmentReminder;
use App\Models\Appointment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/** Envoie un rappel par email la veille de chaque rendez-vous confirmé ou en attente. */
class SendAppointmentReminders extends Command
{
    protected $signature = 'consulat:send-reminders';

    protected $description = 'Envoie les rappels J-1 des rendez-vous';

    public function handle(): int
    {
        $appointments = Appointment::withoutGlobalScopes()
            ->with(['service', 'consulate'])
            ->whereDate('appointment_date', today()->addDay())
            ->whereIn('status', [AppointmentStatus::Pending->value, AppointmentStatus::Confirmed->value])
            ->whereNull('reminder_sent_at')
            ->get();

        foreach ($appointments as $appointment) {
            try {
                Mail::to($appointment->email)->locale($appointment->locale)
                    ->send(new AppointmentReminder($appointment));
                $appointment->forceFill(['reminder_sent_at' => now()])->save();
            } catch (\Throwable $e) {
                Log::warning('Rappel non envoyé', ['reference' => $appointment->reference, 'error' => $e->getMessage()]);
            }
        }

        $this->info($appointments->count().' rappel(s) traité(s).');

        return self::SUCCESS;
    }
}

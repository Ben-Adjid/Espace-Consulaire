<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Mail\ClosureNotice;
use App\Models\Appointment;
use App\Models\ClosedDate;
use App\Models\Consulate;
use App\Models\Service;
use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Alerte de fermeture : un citoyen par adresse, seulement pour les rendez-vous du jour fermé. */
class ClosureAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_closure_alerts_each_affected_citizen_once(): void
    {
        Mail::fake();
        $c = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ]);
        $s = Service::withoutGlobalScopes()->create(['consulate_id' => $c->id, 'slug' => 'passeport', 'name' => ['fr' => 'Passeport'], 'is_active' => true, 'allow_appointment' => true]);
        $slot = TimeSlot::withoutGlobalScopes()->create(['consulate_id' => $c->id, 'weekday' => 1, 'start_time' => '09:00', 'end_time' => '10:00', 'capacity' => 9, 'is_active' => true]);

        $day = now()->addDays(10)->toDateString();
        $make = fn (string $email, AppointmentStatus $status, string $date) => Appointment::withoutGlobalScopes()->create([
            'consulate_id' => $c->id, 'service_id' => $s->id, 'time_slot_id' => $slot->id,
            'reference' => Appointment::generateReference('TCH-SN', $date).uniqid(), 'appointment_date' => $date,
            'start_time' => '09:00', 'end_time' => '10:00', 'full_name' => 'Citoyen', 'email' => $email,
            'phone' => '+221 77 000 00 00', 'status' => $status, 'locale' => 'fr', 'consent_at' => now(),
        ]);
        $make('deux-rdv@example.test', AppointmentStatus::Confirmed, $day);
        $make('deux-rdv@example.test', AppointmentStatus::Pending, $day);     // même adresse : un seul email
        $make('autre-jour@example.test', AppointmentStatus::Confirmed, now()->addDays(11)->toDateString()); // hors fermeture
        $make('annule@example.test', AppointmentStatus::Cancelled, $day);      // annulé : pas concerné

        ClosedDate::create(['consulate_id' => $c->id, 'date' => $day, 'label' => 'Fête nationale']);

        Mail::assertSent(ClosureNotice::class, 1);
        Mail::assertSent(ClosureNotice::class, fn (ClosureNotice $m) => $m->hasTo('deux-rdv@example.test'));
    }
}

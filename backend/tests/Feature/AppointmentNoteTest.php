<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentNote;
use App\Models\Consulate;
use App\Models\Service;
use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Notes internes : enregistrées pour le personnel, absentes de toute réponse publique. */
class AppointmentNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_notes_never_appear_in_the_public_appointment_view(): void
    {
        $c = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ]);
        $s = Service::withoutGlobalScopes()->create(['consulate_id' => $c->id, 'slug' => 'passeport', 'name' => ['fr' => 'Passeport'], 'is_active' => true, 'allow_appointment' => true]);
        $slot = TimeSlot::withoutGlobalScopes()->create(['consulate_id' => $c->id, 'weekday' => 1, 'start_time' => '09:00', 'end_time' => '10:00', 'capacity' => 2, 'is_active' => true]);
        $a = Appointment::withoutGlobalScopes()->create([
            'consulate_id' => $c->id, 'service_id' => $s->id, 'time_slot_id' => $slot->id,
            'reference' => Appointment::generateReference('TCH-SN', now()->toDateString()), 'appointment_date' => now()->toDateString(),
            'start_time' => '09:00', 'end_time' => '10:00', 'full_name' => 'Citoyen', 'email' => 'c@example.test',
            'phone' => '+221 77 000 00 00', 'status' => AppointmentStatus::Confirmed, 'locale' => 'fr', 'consent_at' => now(),
        ]);
        $a->issueAccessToken();
        AppointmentNote::create(['appointment_id' => $a->id, 'body' => 'Dossier incomplet, appeler le demandeur.']);

        $response = $this->getJson("/api/v1/tchad-senegal/appointments/{$a->reference}?token={$a->plainAccessToken}")->assertOk();

        $this->assertStringNotContainsString('Dossier incomplet', $response->getContent());
        $this->assertArrayNotHasKey('notes', $response->json('data'));
    }
}

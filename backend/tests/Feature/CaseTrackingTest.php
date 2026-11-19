<?php

namespace Tests\Feature;

use App\Actions\UpdateCaseStatus;
use App\Enums\AppointmentStatus;
use App\Enums\CaseStatus;
use App\Mail\CaseStatusMail;
use App\Models\Appointment;
use App\Models\Consulate;
use App\Models\Service;
use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Suivi du dossier : étapes, message au citoyen, avis par email, exposition dans l'API. */
class CaseTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function appointment(): Appointment
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
            'start_time' => '09:00', 'end_time' => '10:00', 'full_name' => 'Citoyen', 'email' => 'citoyen@example.test',
            'phone' => '+221 77 000 00 00', 'status' => AppointmentStatus::Confirmed, 'locale' => 'fr', 'consent_at' => now(),
        ]);
        $a->issueAccessToken();

        return $a;
    }

    public function test_ready_status_notifies_the_citizen_with_the_message(): void
    {
        Mail::fake();
        $a = $this->appointment();

        app(UpdateCaseStatus::class)->execute($a, CaseStatus::Ready, 'Passeport disponible au guichet 2.');

        $this->assertSame(CaseStatus::Ready, $a->fresh()->case_status);
        Mail::assertSent(CaseStatusMail::class, fn (CaseStatusMail $m) => $m->hasTo('citoyen@example.test'));
    }

    public function test_case_status_is_visible_through_the_personal_link(): void
    {
        Mail::fake();
        $a = $this->appointment();
        $token = $a->plainAccessToken;
        app(UpdateCaseStatus::class)->execute($a, CaseStatus::Processing, 'Dossier transmis.');

        $this->getJson("/api/v1/tchad-senegal/appointments/{$a->reference}?token={$token}")
            ->assertOk()
            ->assertJsonPath('data.case_status', 'processing')
            ->assertJsonPath('data.case_status_label', 'En traitement')
            ->assertJsonPath('data.case_note', 'Dossier transmis.');
    }
}

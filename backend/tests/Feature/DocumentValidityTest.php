<?php

namespace Tests\Feature;

use App\Mail\ValidityReminderMail;
use App\Models\Appointment;
use App\Models\Citizen;
use App\Models\Consulate;
use App\Models\PreRequest;
use App\Models\PreRequestEvent;
use App\Models\RequestDocument;
use App\Models\Service;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Validité : date d'expiration des pièces, validité d'un document délivré, rappels une seule fois, chronologie, cas particuliers. */
class DocumentValidityTest extends TestCase
{
    use RefreshDatabase;

    private Consulate $c;

    private Citizen $citizen;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(RequestDocument::DISK);
        Mail::fake();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->c = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
        $this->service = Service::withoutGlobalScopes()->create(['consulate_id' => $this->c->id, 'slug' => 'carte-consulaire', 'name' => ['fr' => 'Carte consulaire'], 'is_active' => true, 'allow_appointment' => true]);
        $this->citizen = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->c->id, 'full_name' => 'Amina', 'email' => 'amina@example.test', 'phone' => '+221 77 000 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Sénégal',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('acte.pdf', "%PDF-1.4\n1 0 obj << >> endobj\n%%EOF");
    }

    public function test_a_document_can_carry_an_expiry_date_and_shows_when_it_is_near(): void
    {
        Sanctum::actingAs($this->citizen);
        $id = $this->postJson('/api/v1/tchad-senegal/citizen/requests', ['service' => 'carte-consulaire'])->json('data.id');

        $this->postJson("/api/v1/tchad-senegal/citizen/requests/{$id}/documents", [
            'label' => 'Passeport', 'file' => $this->pdf(), 'expires_on' => now()->addDays(20)->toDateString(),
        ])->assertCreated()->assertJsonPath('data.expiry_state', 'soon');

        $this->postJson("/api/v1/tchad-senegal/citizen/requests/{$id}/documents", [
            'label' => 'Acte', 'file' => $this->pdf(), 'expires_on' => now()->subDay()->toDateString(),
        ])->assertUnprocessable();
    }

    public function test_reminders_are_sent_once_for_documents_and_validities_ending_within_thirty_days(): void
    {
        $pre = PreRequest::withoutGlobalScopes()->create([
            'consulate_id' => $this->c->id, 'citizen_id' => $this->citizen->id, 'service_id' => $this->service->id,
            'status' => 'validated', 'submitted_at' => now(), 'valid_until' => now()->addDays(10)->toDateString(),
        ]);
        $pre->documents()->forceCreate([
            'label' => 'Passeport', 'stored_path' => 'pre-requests/x', 'original_name' => 'p.pdf', 'mime' => 'application/pdf',
            'size' => 10, 'sha256' => str_repeat('a', 64), 'status' => 'accepted', 'expires_on' => now()->addDays(5)->toDateString(),
        ]);

        $this->artisan('consulat:send-validity-reminders')->assertSuccessful();
        $this->artisan('consulat:send-validity-reminders')->assertSuccessful();

        Mail::assertSent(ValidityReminderMail::class, 2);
        $this->assertNotNull($pre->refresh()->validity_reminded_at);
    }

    public function test_the_timeline_merges_the_file_the_booking_and_the_expiries_without_internal_events(): void
    {
        $pre = PreRequest::withoutGlobalScopes()->create([
            'consulate_id' => $this->c->id, 'citizen_id' => $this->citizen->id, 'service_id' => $this->service->id,
            'status' => 'validated', 'submitted_at' => now()->subDays(3),
        ]);
        PreRequestEvent::create(['pre_request_id' => $pre->id, 'type' => 'submitted', 'actor' => 'citizen']);
        PreRequestEvent::create(['pre_request_id' => $pre->id, 'type' => 'assigned', 'actor' => 'staff', 'note' => 'Attribué à Agent']);
        Appointment::withoutGlobalScopes()->create([
            'consulate_id' => $this->c->id, 'service_id' => $this->service->id, 'time_slot_id' => \App\Models\TimeSlot::withoutGlobalScopes()->create(['consulate_id' => $this->c->id, 'weekday' => 1, 'start_time' => '09:00', 'end_time' => '10:00', 'capacity' => 5, 'is_active' => true])->id,
            'reference' => 'TCH-SN-T1', 'appointment_date' => now()->addDays(2)->toDateString(), 'start_time' => '09:00', 'end_time' => '10:00',
            'full_name' => 'Amina', 'email' => 'amina@example.test', 'phone' => '+221 77 000 00 00', 'status' => 'confirmed',
            'locale' => 'fr', 'consent_at' => now(), 'citizen_id' => $this->citizen->id, 'pre_request_id' => $pre->id,
        ]);

        Sanctum::actingAs($this->citizen);
        $items = $this->getJson('/api/v1/tchad-senegal/citizen/timeline')->assertOk()->json('data');

        $titles = collect($items)->pluck('title')->all();
        $this->assertContains('Rendez-vous pris', $titles);
        $this->assertNotContains('Attribué à Agent', collect($items)->pluck('detail')->all());
        $this->assertSame('rendez-vous', collect($items)->firstWhere('title', 'Rendez-vous pris')['kind']);
    }

    public function test_a_special_case_is_shown_to_the_citizen_without_inventing_its_rules(): void
    {
        $pre = PreRequest::withoutGlobalScopes()->create([
            'consulate_id' => $this->c->id, 'citizen_id' => $this->citizen->id, 'service_id' => $this->service->id,
            'status' => 'returned', 'submitted_at' => now(), 'special_case' => 'lost_document',
        ]);

        Sanctum::actingAs($this->citizen);
        $this->getJson("/api/v1/tchad-senegal/citizen/requests/{$pre->id}")->assertOk()
            ->assertJsonPath('data.special_case', 'lost_document')
            ->assertJsonPath('data.special_case_label', 'Document perdu ou volé');
    }
}

<?php

namespace Tests\Feature\Api;

use App\Enums\AppointmentStatus;
use App\Models\Citizen;
use App\Models\Appointment;
use App\Models\ClosedDate;
use App\Models\Consulate;
use App\Models\ContactMessage;
use App\Models\Service;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Tests de l'API publique v1 : isolation par consulat, réservation, jeton, annulation, RGPD. */
class PublicApiTest extends TestCase
{
    private Citizen $citizen;

    use RefreshDatabase;

    private Consulate $chad;

    private Consulate $cameroon;

    private Service $passport;

    private TimeSlot $slot;

    private Carbon $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->chad = $this->makeConsulate('tchad-senegal', 'TCH-SN');
        $this->cameroon = $this->makeConsulate('cameroun-senegal', 'CMR-SN');

        $this->passport = Service::withoutGlobalScopes()->create([
            'consulate_id' => $this->chad->id, 'slug' => 'passeport', 'name' => ['fr' => 'Passeport', 'en' => 'Passport'],
            'is_active' => true, 'allow_appointment' => true, 'fee' => '60 000 FCFA',
        ]);

        $this->slot = TimeSlot::withoutGlobalScopes()->create([
            'consulate_id' => $this->chad->id, 'weekday' => 1, 'start_time' => '09:00', 'end_time' => '10:00',
            'capacity' => 2, 'is_active' => true,
        ]);

        // Une semaine de marge : l'annulation exige au moins 24 h avant le rendez-vous, quelle que soit l'heure du test.
        $this->date = Carbon::today()->addDays(config('booking.min_lead_days') + 7);
        while ($this->date->dayOfWeek !== 1) {
            $this->date->addDay();
        }

        // Par défaut, les réservations se font avec un compte citoyen actif (obligatoire).
        $this->citizen = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->chad->id, 'full_name' => 'Citoyen Test', 'email' => 'citoyen@example.test',
            'phone' => '+221 77 111 11 11', 'password' => 'mot-de-passe-test', 'nationality' => 'Tchad',
            'residence_country' => 'Sénégal', 'status' => Citizen::STATUS_ACTIVE,
        ]);
        Sanctum::actingAs($this->citizen);
    }

    /** Requête sans identité : pour tester l'accès par lien personnel. */
    private function anonymous(): void
    {
        $this->app['auth']->forgetGuards();
    }

    private function makeConsulate(string $slug, string $prefix): Consulate
    {
        return Consulate::create([
            'slug' => $slug, 'ref_prefix' => $prefix, 'name' => ['fr' => $slug],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'],
            'languages' => ['fr', 'en', 'ar'], 'primary_color' => '#002664', 'is_active' => true,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'service' => 'passeport',
            'date' => $this->date->toDateString(),
            'slot_id' => $this->slot->id,
            'full_name' => 'Visiteur Test',
            'email' => 'visiteur@example.test',
            'phone' => '+221 77 000 00 00',
            'consent' => true,
            'nationality' => true,
            'residence_country' => 'Sénégal',
        ], $overrides);
    }

    public function test_consulate_endpoint_returns_theme_and_languages(): void
    {
        $this->getJson('/api/v1/tchad-senegal')
            ->assertOk()
            ->assertJsonPath('data.slug', 'tchad-senegal')
            ->assertJsonPath('data.theme.primary', '#002664')
            ->assertJsonPath('data.languages.0', 'fr');
    }

    public function test_unknown_consulate_returns_json_404(): void
    {
        $this->getJson('/api/v1/inexistant')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_content_is_localized_from_query_param(): void
    {
        $this->getJson('/api/v1/tchad-senegal/services?lang=en')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Passport');
    }

    public function test_booking_requires_consent(): void
    {
        $this->postJson('/api/v1/tchad-senegal/appointments', $this->payload(['consent' => false]))
            ->assertUnprocessable();
    }

    public function test_booking_returns_reference_and_one_time_token(): void
    {
        $response = $this->postJson('/api/v1/tchad-senegal/appointments', $this->payload())
            ->assertCreated()
            ->assertJsonStructure(['reference', 'access_token', 'data']);

        $appointment = Appointment::withoutGlobalScopes()->first();
        $this->assertStringStartsWith('TCH-SN-', $response->json('reference'));
        // Le jeton n'est jamais stocké en clair.
        $this->assertNotSame($response->json('access_token'), $appointment->access_token_hash);
        $this->assertNotNull($appointment->consent_at);
    }

    public function test_booking_on_behalf_of_a_minor_child_is_recorded(): void
    {
        $this->postJson('/api/v1/tchad-senegal/appointments', $this->payload([
            'on_behalf_name' => 'Enfant Mineur', 'on_behalf_relation' => 'child',
        ]))->assertCreated()->assertJsonPath('data.on_behalf_name', 'Enfant Mineur');

        $this->assertSame('child', Appointment::withoutGlobalScopes()->first()->on_behalf_relation);
    }

    public function test_proxy_booking_requires_the_relationship(): void
    {
        $this->postJson('/api/v1/tchad-senegal/appointments', $this->payload(['on_behalf_name' => 'Personne Représentée']))
            ->assertJsonValidationErrors('on_behalf_relation');
    }

    public function test_appointment_details_need_the_token(): void
    {
        $response = $this->postJson('/api/v1/tchad-senegal/appointments', $this->payload())->assertCreated();
        $this->anonymous();
        $ref = $response->json('reference');
        $token = $response->json('access_token');

        $this->getJson("/api/v1/tchad-senegal/appointments/{$ref}")->assertNotFound();
        $this->getJson("/api/v1/tchad-senegal/appointments/{$ref}?token=mauvais")->assertNotFound();

        $this->getJson("/api/v1/tchad-senegal/appointments/{$ref}?token={$token}")
            ->assertOk()
            ->assertJsonPath('data.reference', $ref)
            ->assertJsonMissingPath('email')
            ->assertJsonMissingPath('notes');
    }

    public function test_visitor_can_cancel_with_token(): void
    {
        $response = $this->postJson('/api/v1/tchad-senegal/appointments', $this->payload())->assertCreated();
        $ref = $response->json('reference');
        $token = $response->json('access_token');

        $this->postJson("/api/v1/tchad-senegal/appointments/{$ref}/cancel?token={$token}")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertNotNull(Appointment::withoutGlobalScopes()->first()->cancelled_at);
    }

    public function test_cancellation_is_refused_too_close_to_the_appointment(): void
    {
        config(['booking.cancel_min_hours' => 100000]);
        $response = $this->postJson('/api/v1/tchad-senegal/appointments', $this->payload())->assertCreated();

        $this->postJson("/api/v1/tchad-senegal/appointments/{$response->json('reference')}/cancel?token={$response->json('access_token')}")
            ->assertUnprocessable();
    }

    public function test_closed_day_is_not_bookable(): void
    {
        ClosedDate::withoutGlobalScopes()->create([
            'consulate_id' => $this->chad->id, 'date' => $this->date->toDateString(), 'label' => 'Fête',
        ]);

        $this->getJson('/api/v1/tchad-senegal/availability/dates?service=passeport')
            ->assertOk()
            ->assertJsonMissing([$this->date->toDateString()]);

        $this->postJson('/api/v1/tchad-senegal/appointments', $this->payload())->assertUnprocessable();
    }

    public function test_capacity_is_enforced_through_the_api(): void
    {
        $this->slot->update(['capacity' => 1]);
        $this->postJson('/api/v1/tchad-senegal/appointments', $this->payload())->assertCreated();

        $second = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->chad->id, 'full_name' => 'Deuxième', 'email' => 'deux@example.test',
            'phone' => '+221 77 222 22 22', 'password' => 'mot-de-passe-test', 'nationality' => 'Tchad',
            'residence_country' => 'Sénégal', 'status' => Citizen::STATUS_ACTIVE,
        ]);
        Sanctum::actingAs($second);

        $this->postJson('/api/v1/tchad-senegal/appointments', $this->payload())->assertUnprocessable();
    }

    public function test_slot_of_another_consulate_is_refused(): void
    {
        $foreign = TimeSlot::withoutGlobalScopes()->create([
            'consulate_id' => $this->cameroon->id, 'weekday' => 1, 'start_time' => '09:00', 'end_time' => '10:00',
            'capacity' => 5, 'is_active' => true,
        ]);

        $this->postJson('/api/v1/tchad-senegal/appointments', $this->payload(['slot_id' => $foreign->id]))
            ->assertUnprocessable();
    }

    public function test_ics_export_contains_the_reference(): void
    {
        $response = $this->postJson('/api/v1/tchad-senegal/appointments', $this->payload())->assertCreated();

        $this->get("/api/v1/tchad-senegal/appointments/{$response->json('reference')}/ics?token={$response->json('access_token')}")
            ->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=UTF-8');
    }

    public function test_honeypot_booking_is_silently_ignored(): void
    {
        $this->postJson('/api/v1/tchad-senegal/appointments', $this->payload(['website' => 'spam']))->assertCreated();

        $this->assertSame(0, Appointment::withoutGlobalScopes()->count());
    }

    public function test_booking_uses_the_configured_rate_limiter(): void
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->postJson('/api/v1/tchad-senegal/appointments', $this->payload(['website' => 'spam']))->assertCreated();
        }

        $this->postJson('/api/v1/tchad-senegal/appointments', $this->payload(['website' => 'spam']))
            ->assertTooManyRequests();

        $this->assertSame(0, Appointment::withoutGlobalScopes()->count());
    }

    public function test_contact_form_requires_consent_and_stores_message(): void
    {
        $this->postJson('/api/v1/cameroun-senegal/contact', [
            'name' => 'Visiteur', 'email' => 'v@example.test', 'subject' => 'Question', 'message' => 'Bonjour, une question.',
            '_t' => Crypt::encryptString((string) (time() - 30)),
        ])->assertUnprocessable();

        $this->postJson('/api/v1/cameroun-senegal/contact', [
            'name' => 'Visiteur', 'email' => 'v@example.test', 'subject' => 'Question', 'message' => 'Bonjour, une question.',
            'consent' => true, '_t' => Crypt::encryptString((string) (time() - 30)),
        ])->assertCreated();

        $this->assertDatabaseHas('contact_messages', ['consulate_id' => $this->cameroon->id, 'subject' => 'Question']);
        $this->assertNotNull(ContactMessage::withoutGlobalScopes()->first()->consent_at);
    }

    public function test_contact_honeypot_and_fast_submission_are_discarded(): void
    {
        $this->postJson('/api/v1/tchad-senegal/contact', [
            'name' => 'Robot', 'email' => 'bot@example.test', 'subject' => 'Spam', 'message' => 'Spam spam spam spam.',
            'consent' => true, 'website' => 'x', '_t' => Crypt::encryptString((string) (time() - 30)),
        ])->assertCreated();

        $this->postJson('/api/v1/tchad-senegal/contact', [
            'name' => 'Robot', 'email' => 'fast@example.test', 'subject' => 'Rapide', 'message' => 'Message envoyé instantanément.',
            'consent' => true, '_t' => Crypt::encryptString((string) time()),
        ])->assertCreated();

        $this->assertSame(0, ContactMessage::withoutGlobalScopes()->count());
    }

    public function test_search_only_returns_results_of_the_consulate(): void
    {
        Service::withoutGlobalScopes()->create([
            'consulate_id' => $this->cameroon->id, 'slug' => 'visa-cm', 'name' => ['fr' => 'Visa spécial'],
            'is_active' => true, 'allow_appointment' => true,
        ]);

        $this->getJson('/api/v1/tchad-senegal/search?q=visa&lang=fr')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/tchad-senegal/search?q=passeport&lang=fr')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_reminder_command_sends_one_reminder_per_appointment(): void
    {
        $tomorrow = Carbon::tomorrow();
        // Créneau du jour de demain, pour que le rendez-vous tombe bien demain.
        $slot = TimeSlot::withoutGlobalScopes()->create([
            'consulate_id' => $this->chad->id, 'weekday' => $tomorrow->dayOfWeek, 'start_time' => '11:00',
            'end_time' => '12:00', 'capacity' => 1, 'is_active' => true,
        ]);
        $appointment = Appointment::withoutGlobalScopes()->create([
            'consulate_id' => $this->chad->id, 'service_id' => $this->passport->id, 'time_slot_id' => $slot->id,
            'reference' => 'TCH-SN-TEST01', 'appointment_date' => $tomorrow->toDateString(), 'start_time' => '09:00',
            'end_time' => '10:00', 'full_name' => 'Rappel', 'email' => 'rappel@example.test', 'phone' => '+221770000000',
            'status' => AppointmentStatus::Confirmed, 'locale' => 'fr',
        ]);

        $this->artisan('consulat:send-reminders')->assertSuccessful();
        $this->assertNotNull($appointment->refresh()->reminder_sent_at);

        // Relancer ne renvoie pas un second rappel.
        $this->artisan('consulat:send-reminders')->assertSuccessful();
    }

    public function test_purge_removes_old_personal_data_only(): void
    {
        ContactMessage::withoutGlobalScopes()->create([
            'consulate_id' => $this->chad->id, 'name' => 'Ancien', 'email' => 'a@example.test',
            'subject' => 'Ancien', 'message' => 'Très ancien message.', 'is_read' => true,
        ])->forceFill(['created_at' => now()->subDays(400)])->save();

        ContactMessage::withoutGlobalScopes()->create([
            'consulate_id' => $this->chad->id, 'name' => 'Récent', 'email' => 'r@example.test',
            'subject' => 'Récent', 'message' => 'Message récent.', 'is_read' => false,
        ]);

        $this->artisan('consulat:purge-personal-data')->assertSuccessful();

        $this->assertDatabaseMissing('contact_messages', ['subject' => 'Ancien']);
        $this->assertDatabaseHas('contact_messages', ['subject' => 'Récent']);
    }

    public function test_booking_is_refused_when_residence_is_not_covered_by_this_consulate(): void
    {
        $this->makeConsulate('tchad-cameroun', 'TCM');

        $this->postJson('/api/v1/tchad-senegal/appointments', $this->payload(['residence_country' => 'Cameroun']))
            ->assertStatus(422)
            ->assertJsonPath('suggested_consulate', null)
            ->assertJsonValidationErrors('residence_country');
    }

    public function test_registration_is_refused_outside_covered_residence_and_suggests_the_right_consulate(): void
    {
        $other = $this->makeConsulate('tchad-cameroun', 'TCM');
        $other->update(['covered_countries' => ['Cameroun']]);
        $this->anonymous();

        $this->postJson('/api/v1/tchad-senegal/citizen/register', [
            'full_name' => 'Résident Cameroun', 'email' => 'cm@example.test', 'phone' => '+237 600 000 000',
            'password' => 'mot-de-passe-test', 'password_confirmation' => 'mot-de-passe-test',
            'residence_country' => 'Cameroun', 'nationality' => true, 'consent' => true,
        ])->assertStatus(422)->assertJsonPath('suggested_consulate.slug', 'tchad-cameroun');
    }

    public function test_booking_is_refused_when_declared_residence_differs_from_the_account(): void
    {
        $this->postJson('/api/v1/tchad-senegal/appointments', $this->payload(['residence_country' => 'Cameroun']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('residence_country');
    }

    public function test_suggestion_only_proposes_a_consulate_of_the_same_nationality(): void
    {
        // Un consulat du Cameroun couvre le Cameroun : il ne doit pas être proposé à un Tchadien.
        $cameroonConsulate = $this->makeConsulate('cameroun-cameroun', 'CMC');
        $cameroonConsulate->update(['represented_country' => ['fr' => 'Cameroun'], 'covered_countries' => ['Cameroun']]);

        $this->postJson('/api/v1/tchad-senegal/appointments', $this->payload(['residence_country' => 'Cameroun']))
            ->assertStatus(422)
            ->assertJsonPath('suggested_consulate', null);
    }
    public function test_booking_requires_the_nationality_declaration(): void
    {
        $this->postJson('/api/v1/tchad-senegal/appointments', $this->payload(['nationality' => false]))
            ->assertJsonValidationErrors('nationality');
    }
    private function secondSlot(int $capacity = 2): TimeSlot
    {
        return TimeSlot::withoutGlobalScopes()->create([
            'consulate_id' => $this->chad->id, 'weekday' => 1, 'start_time' => '11:00', 'end_time' => '12:00',
            'capacity' => $capacity, 'is_active' => true,
        ]);
    }

    public function test_visitor_can_move_to_another_slot_with_token(): void
    {
        $second = $this->secondSlot();
        $response = $this->postJson('/api/v1/tchad-senegal/appointments', $this->payload())->assertCreated();

        $this->postJson("/api/v1/tchad-senegal/appointments/{$response->json('reference')}/reschedule?token={$response->json('access_token')}", [
            'date' => $this->date->toDateString(),
            'slot_id' => $second->id,
        ])->assertOk()->assertJsonPath('data.start_time', '11:00');

        $this->assertSame($second->id, Appointment::withoutGlobalScopes()->first()->time_slot_id);
    }

    public function test_reschedule_is_refused_when_target_slot_is_full(): void
    {
        $second = $this->secondSlot(capacity: 1);
        $this->postJson('/api/v1/tchad-senegal/appointments', $this->payload(['slot_id' => $second->id, 'email' => 'autre@example.test']))->assertCreated();

        $response = $this->postJson('/api/v1/tchad-senegal/appointments', $this->payload())->assertCreated();

        $this->postJson("/api/v1/tchad-senegal/appointments/{$response->json('reference')}/reschedule?token={$response->json('access_token')}", [
            'date' => $this->date->toDateString(),
            'slot_id' => $second->id,
        ])->assertUnprocessable();
    }

    public function test_reschedule_needs_the_access_token(): void
    {
        $response = $this->postJson('/api/v1/tchad-senegal/appointments', $this->payload())->assertCreated();
        $this->anonymous();

        $this->postJson("/api/v1/tchad-senegal/appointments/{$response->json('reference')}/reschedule", [
            'date' => $this->date->toDateString(),
            'slot_id' => $this->slot->id,
        ])->assertNotFound();
    }
}

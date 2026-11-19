<?php

namespace Tests\Feature;

use App\Actions\CreateAppointment;
use App\Enums\CaseStatus;
use App\Exceptions\BookingException;
use App\Models\Citizen;
use App\Models\Consulate;
use App\Models\PreRequest;
use App\Models\Service;
use App\Models\ServiceFee;
use App\Models\TimeSlot;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Démarches : rendez-vous réservé seulement avec un dossier validé (si le service l'exige), frais affichés, urgence, statuts de production. */
class DemarchesRulesTest extends TestCase
{
    use RefreshDatabase;

    private Consulate $c;

    private Citizen $citizen;

    private TimeSlot $slot;

    private CarbonImmutable $date;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->c = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
        $this->citizen = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->c->id, 'full_name' => 'Amina', 'email' => 'amina@example.test', 'phone' => '+221 77 000 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Sénégal',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
        $this->date = CarbonImmutable::today()->addDays(3)->startOfDay();
        $this->slot = TimeSlot::withoutGlobalScopes()->create([
            'consulate_id' => $this->c->id, 'weekday' => $this->date->dayOfWeek, 'start_time' => '09:00', 'end_time' => '10:00', 'capacity' => 5, 'is_active' => true,
        ]);
    }

    private function service(bool $requiresFile): Service
    {
        return Service::withoutGlobalScopes()->create([
            'consulate_id' => $this->c->id, 'slug' => $requiresFile ? 'carte' : 'visa', 'name' => ['fr' => 'Service'],
            'is_active' => true, 'allow_appointment' => true, 'requires_validated_file' => $requiresFile,
        ]);
    }

    private function book(Service $service): void
    {
        app(CreateAppointment::class)->execute($this->c, $service, $this->slot, $this->date, [
            'full_name' => 'Amina', 'email' => $this->citizen->email, 'phone' => '+221 77 000 00 00', 'consent' => true, 'citizen_id' => $this->citizen->id,
        ]);
    }

    public function test_a_service_that_requires_a_validated_file_refuses_booking_without_one(): void
    {
        $service = $this->service(true);
        $this->expectException(BookingException::class);
        $this->expectExceptionMessage('dossier en ligne validé');
        $this->book($service);
    }

    public function test_the_same_service_accepts_booking_once_the_file_is_validated(): void
    {
        $service = $this->service(true);
        PreRequest::withoutGlobalScopes()->create(['consulate_id' => $this->c->id, 'citizen_id' => $this->citizen->id, 'service_id' => $service->id, 'status' => 'validated', 'submitted_at' => now(), 'reviewed_at' => now()]);

        $this->book($service);
        $this->assertSame(1, \App\Models\Appointment::withoutGlobalScopes()->whereNotNull('pre_request_id')->count());
    }

    public function test_the_pre_request_view_shows_the_current_fee_and_urgency(): void
    {
        $service = $this->service(false);
        ServiceFee::withoutGlobalScopes()->create([
            'service_id' => $service->id, 'amount' => 15000, 'currency' => 'FCFA', 'legal_reference' => 'Arrêté de test',
            'effective_from' => now()->subYear()->toDateString(),
        ]);
        $pre = PreRequest::withoutGlobalScopes()->create(['consulate_id' => $this->c->id, 'citizen_id' => $this->citizen->id, 'service_id' => $service->id, 'status' => 'draft', 'is_urgent' => true]);

        Sanctum::actingAs($this->citizen);
        $this->getJson("/api/v1/tchad-senegal/citizen/requests/{$pre->id}")->assertOk()
            ->assertJsonPath('data.fee', '15 000 FCFA')
            ->assertJsonPath('data.is_urgent', true);
    }

    public function test_production_and_shipping_are_part_of_the_case_follow_up(): void
    {
        $this->assertSame('in_production', CaseStatus::InProduction->value);
        $this->assertSame('shipped', CaseStatus::Shipped->value);
        $this->assertArrayHasKey('shipped', CaseStatus::options());
    }
}

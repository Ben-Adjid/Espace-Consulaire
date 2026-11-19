<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Consulate;
use App\Models\ConsularReceipt;
use App\Models\PreRequest;
use App\Models\Service;
use App\Models\Citizen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Anti-doublon : un seul reçu valide par rendez-vous, et par démarche ; un reçu annulé libère la démarche. */
class ReceiptDuplicateGuardTest extends TestCase
{
    use RefreshDatabase;

    private Consulate $c;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->c = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ]);
        $this->service = Service::withoutGlobalScopes()->create(['consulate_id' => $this->c->id, 'slug' => 'carte-consulaire-papier', 'name' => ['fr' => 'Carte'], 'is_active' => true, 'allow_appointment' => true]);
    }

    private function appointment(?int $preRequestId = null): Appointment
    {
        $slot = \App\Models\TimeSlot::withoutGlobalScopes()->create(['consulate_id' => $this->c->id, 'weekday' => 1, 'start_time' => '09:00', 'end_time' => '10:00', 'capacity' => 5, 'is_active' => true]);

        return Appointment::withoutGlobalScopes()->create([
            'consulate_id' => $this->c->id, 'service_id' => $this->service->id, 'time_slot_id' => $slot->id,
            'reference' => 'REF-'.uniqid(), 'appointment_date' => now()->toDateString(), 'start_time' => '09:00', 'end_time' => '10:00',
            'full_name' => 'Amina', 'email' => uniqid().'@example.test', 'phone' => '+221 77 000 00 00', 'status' => 'confirmed',
            'locale' => 'fr', 'consent_at' => now(), 'pre_request_id' => $preRequestId,
        ]);
    }

    private function receipt(Appointment $a): ConsularReceipt
    {
        return ConsularReceipt::create([
            'consulate_id' => $this->c->id, 'appointment_id' => $a->id, 'service_id' => $this->service->id,
            'payer_name' => 'Amina', 'amount' => 2000, 'currency' => 'FCFA', 'method' => 'especes',
        ]);
    }

    public function test_an_appointment_cannot_be_paid_twice(): void
    {
        $a = $this->appointment();
        $this->receipt($a);

        $this->expectException(\LogicException::class);
        $this->receipt($a);
    }

    public function test_a_procedure_cannot_be_paid_twice_through_two_appointments(): void
    {
        $citizen = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->c->id, 'full_name' => 'Amina', 'email' => 'amina@example.test', 'phone' => '+221 77 000 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Sénégal', 'status' => Citizen::STATUS_ACTIVE,
        ]);
        $pre = PreRequest::withoutGlobalScopes()->create(['consulate_id' => $this->c->id, 'citizen_id' => $citizen->id, 'service_id' => $this->service->id, 'status' => 'validated']);
        $first = $this->appointment($pre->id);
        $second = $this->appointment($pre->id);
        $this->receipt($first);

        $this->expectException(\LogicException::class);
        $this->receipt($second);
    }

    public function test_a_cancelled_receipt_frees_the_appointment_for_a_new_payment(): void
    {
        $a = $this->appointment();
        $old = $this->receipt($a);
        $old->update(['cancelled_at' => now(), 'cancel_reason' => 'Erreur de montant']);

        $new = $this->receipt($a);
        $this->assertNotNull($new->id);
    }
}

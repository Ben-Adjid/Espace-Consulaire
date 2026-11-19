<?php

namespace Tests\Feature;

use App\Actions\CheckInAppointment;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Consulate;
use App\Models\Service;
use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/** Arrivée au guichet et tickets de file d'attente. */
class CheckInTest extends TestCase
{
    use RefreshDatabase;

    private Consulate $consulate;

    private TimeSlot $slot;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->consulate = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ]);
        $this->service = Service::withoutGlobalScopes()->create([
            'consulate_id' => $this->consulate->id, 'slug' => 'passeport', 'name' => ['fr' => 'Passeport'],
            'is_active' => true, 'allow_appointment' => true,
        ]);
        $this->slot = TimeSlot::withoutGlobalScopes()->create([
            'consulate_id' => $this->consulate->id, 'weekday' => now()->dayOfWeek, 'start_time' => '09:00', 'end_time' => '10:00',
            'capacity' => 5, 'is_active' => true,
        ]);
    }

    private function appointment(string $date, AppointmentStatus $status = AppointmentStatus::Confirmed): Appointment
    {
        return Appointment::withoutGlobalScopes()->create([
            'consulate_id' => $this->consulate->id, 'service_id' => $this->service->id, 'time_slot_id' => $this->slot->id,
            'reference' => Appointment::generateReference('TCH-SN', $date), 'appointment_date' => $date,
            'start_time' => '09:00', 'end_time' => '10:00', 'full_name' => 'Citoyen', 'email' => uniqid().'@example.test',
            'phone' => '+221 77 000 00 00', 'status' => $status, 'locale' => 'fr', 'consent_at' => now(),
        ]);
    }

    public function test_tickets_are_numbered_in_arrival_order_for_the_day(): void
    {
        $first = $this->appointment(now()->toDateString());
        $second = $this->appointment(now()->toDateString());

        app(CheckInAppointment::class)->execute($second);
        app(CheckInAppointment::class)->execute($first);

        $this->assertSame(1, $second->fresh()->queue_number);
        $this->assertSame(2, $first->fresh()->queue_number);
        $this->assertSame('A-002', CheckInAppointment::label($first->fresh()->queue_number));
    }

    public function test_checking_in_twice_keeps_the_same_ticket(): void
    {
        $a = $this->appointment(now()->toDateString());
        app(CheckInAppointment::class)->execute($a);
        app(CheckInAppointment::class)->execute($a->fresh());

        $this->assertSame(1, $a->fresh()->queue_number);
    }

    public function test_cannot_check_in_for_another_day_or_a_cancelled_appointment(): void
    {
        $this->expectException(RuntimeException::class);
        app(CheckInAppointment::class)->execute($this->appointment(now()->addDay()->toDateString()));
    }

    public function test_cancelled_appointment_cannot_be_checked_in(): void
    {
        $this->expectException(RuntimeException::class);
        app(CheckInAppointment::class)->execute($this->appointment(now()->toDateString(), AppointmentStatus::Cancelled));
    }
}

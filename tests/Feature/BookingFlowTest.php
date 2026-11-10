<?php

namespace Tests\Feature;

use App\Livewire\BookingForm;
use App\Models\Appointment;
use App\Models\Consulate;
use App\Models\Service;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Parcours complet du formulaire public : service → date → créneau → coordonnées. */
class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_can_book_through_the_livewire_form(): void
    {
        $consulate = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'languages' => ['fr'], 'is_active' => true,
        ]);
        $service = Service::withoutGlobalScopes()->create([
            'consulate_id' => $consulate->id, 'slug' => 'passeport', 'name' => ['fr' => 'Passeport'],
            'is_active' => true, 'allow_appointment' => true,
        ]);
        $slot = TimeSlot::withoutGlobalScopes()->create([
            'consulate_id' => $consulate->id, 'weekday' => 1, 'start_time' => '09:00', 'end_time' => '10:00',
            'capacity' => 1, 'is_active' => true,
        ]);

        $date = Carbon::today()->addDays(config('booking.min_lead_days'));
        while ($date->dayOfWeek !== 1) {
            $date->addDay();
        }

        Livewire::test(BookingForm::class, ['consulateId' => $consulate->id, 'locale' => 'fr', 'preselected' => ''])
            ->set('serviceId', $service->id)
            ->call('selectDate', $date->toDateString())
            ->set('slotId', $slot->id)
            ->set('full_name', 'Visiteur Test')
            ->set('email', 'visiteur@example.test')
            ->set('phone', '+221 77 000 00 00')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect();

        $appointment = Appointment::withoutGlobalScopes()->first();
        $this->assertNotNull($appointment);
        $this->assertSame($consulate->id, $appointment->consulate_id);
        $this->assertSame($date->toDateString(), $appointment->appointment_date->toDateString());
    }
}

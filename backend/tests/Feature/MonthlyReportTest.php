<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Consulate;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Services\MonthlyReport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Rapport mensuel : comptage par service, présences, absences, annulations, export CSV. */
class MonthlyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_report_counts_presence_absence_and_cancellation_per_service(): void
    {
        $c = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ]);
        $s = Service::withoutGlobalScopes()->create(['consulate_id' => $c->id, 'slug' => 'passeport', 'name' => ['fr' => 'Passeport'], 'is_active' => true, 'allow_appointment' => true]);
        $slot = TimeSlot::withoutGlobalScopes()->create(['consulate_id' => $c->id, 'weekday' => 1, 'start_time' => '09:00', 'end_time' => '10:00', 'capacity' => 9, 'is_active' => true]);

        $month = CarbonImmutable::create(2026, 10, 15);
        $make = function (string $status, ?string $checkedIn, string $email) use ($c, $s, $slot, $month) {
            return Appointment::withoutGlobalScopes()->create([
                'consulate_id' => $c->id, 'service_id' => $s->id, 'time_slot_id' => $slot->id,
                'reference' => Appointment::generateReference('TCH-SN', $month->toDateString()).$email,
                'appointment_date' => $month->toDateString(), 'start_time' => '09:00', 'end_time' => '10:00',
                'full_name' => 'X', 'email' => $email.'@example.test', 'phone' => '+221 77 000 00 00',
                'status' => AppointmentStatus::from($status), 'checked_in_at' => $checkedIn, 'locale' => 'fr', 'consent_at' => now(),
            ]);
        };
        $make('confirmed', now()->toDateTimeString(), 'present');   // présent
        $make('completed', null, 'termine');                         // terminé = présent
        $make('confirmed', null, 'absent');                          // absent (date passée, non arrivé)
        $make('cancelled', null, 'annule');                          // annulé

        $report = app(MonthlyReport::class)->build($c, $month);

        $this->assertSame('2026-10', $report['month']);
        $this->assertSame(4, $report['totals']['rendez_vous']);
        $this->assertSame(2, $report['totals']['presents']);
        $this->assertSame(1, $report['totals']['absences']);
        $this->assertSame(1, $report['totals']['annules']);
        $this->assertStringContainsString('Passeport;4;2;1;1', app(MonthlyReport::class)->toCsv($report));
    }
}

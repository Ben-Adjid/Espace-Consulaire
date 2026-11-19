<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Filament\Admin\Pages\TableauGlobal;
use App\Models\Appointment;
use App\Models\Consulate;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Models\User;
use App\Services\GlobalDashboard;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Tableau global : indicateurs par consulat et totaux ; accès réservé au super admin. */
class GlobalDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function consulate(string $slug, string $prefix): Consulate
    {
        return Consulate::create([
            'slug' => $slug, 'ref_prefix' => $prefix, 'name' => ['fr' => "Consulat $slug"],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ]);
    }

    private function appointment(Consulate $c, AppointmentStatus $status, ?string $checkedIn = null): void
    {
        $s = Service::withoutGlobalScopes()->firstOrCreate(
            ['consulate_id' => $c->id, 'slug' => 'passeport'],
            ['name' => ['fr' => 'Passeport'], 'is_active' => true, 'allow_appointment' => true],
        );
        $slot = TimeSlot::withoutGlobalScopes()->firstOrCreate(
            ['consulate_id' => $c->id, 'weekday' => 1, 'start_time' => '09:00'],
            ['end_time' => '10:00', 'capacity' => 99, 'is_active' => true],
        );
        $date = now()->startOfMonth()->addDay()->toDateString();
        Appointment::withoutGlobalScopes()->create([
            'consulate_id' => $c->id, 'service_id' => $s->id, 'time_slot_id' => $slot->id,
            'reference' => Appointment::generateReference($c->ref_prefix, $date).uniqid(), 'appointment_date' => $date,
            'start_time' => '09:00', 'end_time' => '10:00', 'full_name' => 'Citoyen', 'email' => uniqid().'@example.test',
            'phone' => '+221 77 000 00 00', 'status' => $status, 'locale' => 'fr', 'consent_at' => now(),
            'checked_in_at' => $checkedIn,
        ]);
    }

    public function test_counts_per_consulate_exclude_cancelled_and_count_presence(): void
    {
        $a = $this->consulate('tchad-senegal', 'TCH-SN');
        $b = $this->consulate('tchad-togo', 'TCH-TG');

        $this->appointment($a, AppointmentStatus::Confirmed);
        $this->appointment($a, AppointmentStatus::Confirmed, now()->toDateTimeString()); // présent
        $this->appointment($a, AppointmentStatus::Cancelled);                             // exclu
        $this->appointment($b, AppointmentStatus::Completed);                             // présent

        $data = app(GlobalDashboard::class)->build(CarbonImmutable::now());
        $rows = collect($data['rows'])->keyBy('slug');

        $this->assertSame(2, $rows['tchad-senegal']['appointments']);
        $this->assertSame(1, $rows['tchad-senegal']['present']);
        $this->assertSame(1, $rows['tchad-togo']['appointments']);
        $this->assertSame(2, $data['totals']['consulates']);
        $this->assertSame(3, $data['totals']['appointments']);
        $this->assertSame(2, $data['totals']['present']);
    }

    public function test_only_super_admin_can_open_the_global_dashboard(): void
    {
        foreach ([User::ROLE_SUPER_ADMIN, User::ROLE_CONSULATE_ADMIN, 'staff'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
        $super = User::factory()->create(['is_active' => true]);
        $super->assignRole(User::ROLE_SUPER_ADMIN);
        $this->actingAs($super);
        $this->assertTrue(TableauGlobal::canAccess());
        $this->get('/admin/tableau-global')->assertOk()->assertSee('Par consulat');

        $consul = User::factory()->create(['is_active' => true]);
        $consul->assignRole(User::ROLE_CONSULATE_ADMIN);
        $this->actingAs($consul);
        $this->assertFalse(TableauGlobal::canAccess());
    }
}

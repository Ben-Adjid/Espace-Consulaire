<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\Consulate;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_activity_is_recorded_without_field_values(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $consulate = $this->consulate('tchad-senegal');
        $admin = User::factory()->create(['consulate_id' => $consulate->id]);
        $admin->assignRole(User::ROLE_CONSULATE_ADMIN);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Service::create([
            'consulate_id' => $consulate->id,
            'slug' => 'passeport',
            'name' => ['fr' => 'Passeport privé'],
            'is_active' => true,
        ]);

        $log = ActivityLog::query()->sole();

        $this->assertSame($admin->id, $log->actor_id);
        $this->assertSame($consulate->id, $log->consulate_id);
        $this->assertSame('created', $log->action);
        $this->assertContains('name', $log->changed_fields);
        $this->assertStringNotContainsString('Passeport privé', json_encode($log->changed_fields));
    }

    public function test_consulate_admin_only_sees_activity_for_their_consulate(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $chad = $this->consulate('tchad-senegal');
        $cameroon = $this->consulate('cameroun-senegal');
        $admin = User::factory()->create(['consulate_id' => $chad->id]);
        $admin->assignRole(User::ROLE_CONSULATE_ADMIN);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $chadLog = ActivityLog::create([
            'consulate_id' => $chad->id,
            'action' => 'updated',
            'subject_type' => Service::class,
            'subject_id' => '1',
        ]);
        $cameroonLog = ActivityLog::create([
            'consulate_id' => $cameroon->id,
            'action' => 'updated',
            'subject_type' => Service::class,
            'subject_id' => '2',
        ]);

        $visible = ActivityLogResource::getEloquentQuery()->pluck('id')->all();

        $this->assertContains($chadLog->id, $visible);
        $this->assertNotContains($cameroonLog->id, $visible);
    }

    private function consulate(string $slug): Consulate
    {
        return Consulate::create([
            'slug' => $slug,
            'ref_prefix' => strtoupper(substr($slug, 0, 3)),
            'name' => ['fr' => $slug],
            'represented_country' => ['fr' => 'Pays'],
            'host_country' => ['fr' => 'Sénégal'],
            'languages' => ['fr'],
            'is_active' => true,
        ]);
    }
}

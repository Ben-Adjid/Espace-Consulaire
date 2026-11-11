<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Consulate;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Isolation multi-consulats côté administration (scope Filament + policies). */
class TenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_consulate_admin_only_sees_own_consulate_data(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $chad = $this->consulate('tchad-senegal');
        $cameroon = $this->consulate('cameroun-senegal');

        ContactMessage::withoutGlobalScopes()->create([
            'consulate_id' => $cameroon->id, 'name' => 'Autre', 'email' => 'x@example.test',
            'subject' => 'Sujet', 'message' => 'Message de l’autre consulat.', 'is_read' => false,
        ]);
        Service::withoutGlobalScopes()->create([
            'consulate_id' => $cameroon->id, 'slug' => 's', 'name' => ['fr' => 'S'], 'is_active' => true,
        ]);

        $admin = User::factory()->create(['consulate_id' => $chad->id]);
        $admin->assignRole(User::ROLE_CONSULATE_ADMIN);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->assertSame(0, ContactMessage::query()->count());
        $this->assertSame(0, Service::query()->count());
    }

    public function test_super_admin_sees_all_consulates(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->consulate('tchad-senegal');
        $this->consulate('cameroun-senegal');

        $super = User::factory()->create(['consulate_id' => null]);
        $super->assignRole(User::ROLE_SUPER_ADMIN);
        $this->actingAs($super);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->assertSame(2, Consulate::query()->count());
    }

    private function consulate(string $slug): Consulate
    {
        return Consulate::create([
            'slug' => $slug, 'ref_prefix' => 'X', 'name' => ['fr' => $slug],
            'represented_country' => ['fr' => 'P'], 'host_country' => ['fr' => 'S'],
            'languages' => ['fr'], 'is_active' => true,
        ]);
    }
}

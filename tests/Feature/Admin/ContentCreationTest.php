<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\ServiceResource\Pages\CreateService;
use App\Models\Consulate;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Un admin de consulat doit pouvoir créer du contenu pour SON consulat. */
class ContentCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_consulate_admin_can_create_a_service_for_own_consulate(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $consulate = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'languages' => ['fr'], 'is_active' => true,
        ]);

        $admin = User::factory()->create(['consulate_id' => $consulate->id]);
        $admin->assignRole(User::ROLE_CONSULATE_ADMIN);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(CreateService::class)
            ->fillForm(['name' => 'Passeport', 'slug' => 'passeport', 'icon' => 'passport'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('services', ['slug' => 'passeport', 'consulate_id' => $consulate->id]);
        $this->assertSame(1, Service::withoutGlobalScopes()->count());
    }
}

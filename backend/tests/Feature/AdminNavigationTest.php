<?php

namespace Tests\Feature;

use App\Models\Consulate;
use App\Models\User;
use App\Providers\Filament\AdminPanelProvider;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Navigation admin : retour au site public et lien vers le bon consulat. */
class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_links_back_to_public_site(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Retour au site public', false);
    }

    public function test_consulate_admin_is_sent_to_own_public_site(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $consulate = Consulate::create([
            'slug' => 'cameroun-senegal', 'ref_prefix' => 'CMR', 'name' => ['fr' => 'Cameroun'],
            'represented_country' => ['fr' => 'Cameroun'], 'host_country' => ['fr' => 'Sénégal'],
            'languages' => ['fr'], 'is_active' => true,
        ]);
        $admin = User::factory()->create(['consulate_id' => $consulate->id]);
        $admin->assignRole(User::ROLE_CONSULATE_ADMIN);
        $this->actingAs($admin);

        $this->assertStringEndsWith('/cameroun-senegal/fr', AdminPanelProvider::publicSiteUrl());
    }

    public function test_super_admin_is_sent_to_platform_home(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $super = User::factory()->create(['consulate_id' => null]);
        $super->assignRole(User::ROLE_SUPER_ADMIN);
        $this->actingAs($super);

        $url = AdminPanelProvider::publicSiteUrl();
        $this->assertStringNotContainsString('/fr', $url);
    }
}

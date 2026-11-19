<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\Consulate;
use App\Models\News;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Nettoyage avant remise : tout part, sauf le super administrateur conservé, les rôles et permissions. */
class PurgeDemoDataTest extends TestCase
{
    use RefreshDatabase;

    private function demo(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $c = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ]);
        News::withoutGlobalScopes()->create(['consulate_id' => $c->id, 'slug' => 'n', 'title' => ['fr' => 'Actu']]);
        Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $c->id, 'full_name' => 'Démo', 'email' => 'c@demo.test', 'phone' => '+221 77 000 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Sénégal', 'status' => 'active',
        ]);
        User::factory()->create(['email' => 'consul@demo.test', 'consulate_id' => $c->id])->assignRole('consulate_admin');
        $super = User::factory()->create(['email' => 'superadmin@demo.test', 'consulate_id' => null]);
        $super->assignRole('super_admin');

        return $super;
    }

    public function test_dry_run_deletes_nothing(): void
    {
        $this->demo();
        $this->artisan('consulat:purge-demo', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame(1, Consulate::count());
        $this->assertSame(2, User::count());
    }

    public function test_purge_keeps_only_the_super_admin(): void
    {
        $super = $this->demo();
        $this->artisan('consulat:purge-demo', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, Consulate::count());
        $this->assertSame(0, Citizen::withoutGlobalScopes()->count());
        $this->assertSame(0, News::withoutGlobalScopes()->count());
        $this->assertSame([$super->id], User::pluck('id')->all());
        $this->assertTrue($super->fresh()->isSuperAdmin());
        $this->assertNotEmpty(\Spatie\Permission\Models\Role::pluck('name')->all());
    }

    public function test_refuses_when_the_kept_account_is_not_super_admin(): void
    {
        $this->demo();
        $this->artisan('consulat:purge-demo', ['--force' => true, '--keep' => 'consul@demo.test'])->assertFailed();

        $this->assertSame(1, Consulate::count());
    }
}

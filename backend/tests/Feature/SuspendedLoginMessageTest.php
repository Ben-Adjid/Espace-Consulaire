<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\Consulate;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Connexion d'un consul dont le consulat est suspendu : message explicite, et message générique si le mot de passe est faux. */
class SuspendedLoginMessageTest extends TestCase
{
    use RefreshDatabase;

    private function suspendedConsul(): void
    {
        Role::firstOrCreate(['name' => User::ROLE_CONSULATE_ADMIN, 'guard_name' => 'web']);
        $c = Consulate::create([
            'slug' => 'tchad-togo', 'ref_prefix' => 'TCH-TG', 'name' => ['fr' => 'Consulat du Tchad au Togo'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Togo'],
            'covered_countries' => ['Togo'], 'languages' => ['fr'], 'is_active' => false,
            'suspended_for_nonpayment' => true,
        ]);
        $u = User::factory()->create(['email' => 'consul-suspendu@example.test', 'consulate_id' => $c->id,
            'is_active' => true, 'password' => bcrypt('MotDePasse-Test-2026')]);
        $u->assignRole(User::ROLE_CONSULATE_ADMIN);
    }

    public function test_correct_password_for_a_suspended_consulate_explains_why(): void
    {
        $this->suspendedConsul();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $component = Livewire::test(Login::class)
            ->fillForm(['email' => 'consul-suspendu@example.test', 'password' => 'MotDePasse-Test-2026'])
            ->call('authenticate');

        $message = (string) $component->errors()->first('data.email');
        $this->assertStringContainsString('Consulat suspendu pour non-paiement', $message);
    }

    public function test_wrong_password_keeps_the_generic_message(): void
    {
        $this->suspendedConsul();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $component = Livewire::test(Login::class)
            ->fillForm(['email' => 'consul-suspendu@example.test', 'password' => 'mauvais'])
            ->call('authenticate');

        $message = (string) $component->errors()->first('data.email');
        $this->assertStringNotContainsString('suspendu', $message);
        $this->assertNotSame('', $message);
    }
}

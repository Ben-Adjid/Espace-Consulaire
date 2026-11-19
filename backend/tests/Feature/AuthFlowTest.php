<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\Citizen;
use App\Models\Consulate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Parcours de connexion : accès protégé, redirection d'un utilisateur déjà connecté, rôle vérifié par le serveur,
 * déconnexion, orientation d'un citoyen, et comptes de démonstration refusés en production.
 */
class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    private Consulate $consulate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->consulate = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function user(string $role, string $email = 'agent@example.test'): User
    {
        $u = User::factory()->create([
            'email' => $email, 'is_active' => true, 'password' => bcrypt('MotDePasse-Test-2026'),
            'consulate_id' => $role === 'super_admin' ? null : $this->consulate->id,
        ]);
        $u->assignRole($role);

        return $u;
    }

    public function test_guest_is_sent_to_login_for_every_protected_page(): void
    {
        foreach (['/admin', '/admin/appointments', '/admin/users', '/admin/file-du-jour'] as $url) {
            $this->get($url)->assertRedirect('/admin/login');
        }
    }

    public function test_guest_opening_a_protected_file_link_is_sent_to_login_not_an_error(): void
    {
        foreach (['/admin/pre-requests/documents/1', '/admin/paiements/1/recu', '/admin/rapport-mensuel/export'] as $url) {
            $this->get($url)->assertRedirect('/admin/login');
        }
        $this->getJson('/admin/pre-requests/documents/1')->assertUnauthorized();
    }

    public function test_logged_in_user_visiting_login_goes_to_dashboard_without_loop(): void
    {
        $this->actingAs($this->user('staff'));

        $this->get('/admin/login')->assertRedirect('/admin');
        $this->get('/admin')->assertOk();
    }

    public function test_each_role_logs_in_and_lands_on_its_dashboard(): void
    {
        foreach (['super_admin' => 'Abonnements à renouveler', 'consulate_admin' => 'Aujourd’hui au consulat', 'staff' => 'Aujourd’hui au consulat'] as $role => $marker) {
            $user = $this->user($role, $role.'@example.test');

            Livewire::test(Login::class)
                ->fillForm(['email' => $user->email, 'password' => 'MotDePasse-Test-2026'])
                ->call('authenticate')
                ->assertHasNoFormErrors()
                ->assertRedirect('/admin');

            $this->assertAuthenticatedAs($user);
            $html = $this->get('/admin')->assertOk()->getContent();
            $this->assertStringContainsString($marker, $html, "Tableau de bord du rôle {$role}");
            if ($role !== 'super_admin') {
                $this->assertStringNotContainsString('Abonnements à renouveler', $html, "Vue plateforme masquée pour {$role}");
            }

            auth()->logout();
        }
    }

    public function test_wrong_password_shows_a_generic_error(): void
    {
        $this->user('staff');

        Livewire::test(Login::class)
            ->fillForm(['email' => 'agent@example.test', 'password' => 'mauvais'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }

    public function test_citizen_trying_the_agent_space_is_redirected_to_citizen_space(): void
    {
        Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->consulate->id, 'full_name' => 'Citoyen', 'email' => 'citoyen@example.test',
            'phone' => '+221 77 000 00 00', 'password' => 'MotDePasse-Test-2026', 'nationality' => 'Tchad',
            'residence_country' => 'Sénégal', 'status' => Citizen::STATUS_ACTIVE,
        ]);

        $component = Livewire::test(Login::class)
            ->fillForm(['email' => 'citoyen@example.test', 'password' => 'MotDePasse-Test-2026'])
            ->call('authenticate');

        $this->assertStringContainsString('compte citoyen', implode(' ', $component->errors()->all()));
        $this->assertGuest();
    }

    public function test_demo_account_is_refused_in_production(): void
    {
        $demo = $this->user('super_admin', 'superadmin@demo.test');
        $this->app['env'] = 'production';

        $this->assertFalse($demo->canAccessPanel(Filament::getPanel('admin')));
        // Même avec une session ouverte, le compte de démonstration ne passe pas.
        $this->actingAs($demo)->get('/admin')->assertForbidden();
    }

    public function test_logout_ends_the_session(): void
    {
        $this->actingAs($this->user('staff'));

        $this->post('/admin/logout')->assertRedirect();
        $this->assertGuest();
        $this->get('/admin')->assertRedirect('/admin/login');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Consulate;
use App\Models\ConsulatePayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Reçus d'abonnement (super admin : tout ; consul : son consulat seulement) et page « Mon abonnement ». */
class SubscriptionUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([User::ROLE_SUPER_ADMIN, User::ROLE_CONSULATE_ADMIN, 'staff'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    private function consulate(string $slug = 'tchad-senegal'): Consulate
    {
        return Consulate::create([
            'slug' => $slug, 'ref_prefix' => strtoupper(substr($slug, 0, 6)), 'name' => ['fr' => 'Consulat du Tchad au Sénégal'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
    }

    private function user(string $role, ?Consulate $c = null): User
    {
        $u = User::factory()->create(['is_active' => true, 'consulate_id' => $c?->id]);
        $u->assignRole($role);

        return $u;
    }

    private function payment(Consulate $c): ConsulatePayment
    {
        return ConsulatePayment::create([
            'consulate_id' => $c->id, 'amount' => 150000, 'currency' => 'XOF', 'paid_at' => now()->toDateString(),
            'period_start' => now()->toDateString(), 'period_end' => now()->addYear()->toDateString(), 'method' => 'virement',
        ]);
    }

    public function test_receipt_access_follows_the_consulate(): void
    {
        $own = $this->consulate('tchad-senegal');
        $other = $this->consulate('tchad-togo');
        $payment = $this->payment($own);

        $this->actingAs($this->user(User::ROLE_SUPER_ADMIN))->get('/admin/paiements/'.$payment->id.'/recu')
            ->assertOk()->assertSee('150 000,00 XOF');

        $this->actingAs($this->user(User::ROLE_CONSULATE_ADMIN, $own))->get('/admin/paiements/'.$payment->id.'/recu')
            ->assertOk();

        $this->actingAs($this->user(User::ROLE_CONSULATE_ADMIN, $other))->get('/admin/paiements/'.$payment->id.'/recu')
            ->assertForbidden();
    }

    public function test_consul_sees_own_subscription_page_and_staff_does_not(): void
    {
        $c = $this->consulate();
        $this->payment($c);

        $this->actingAs($this->user(User::ROLE_CONSULATE_ADMIN, $c))->get('/admin/mon-abonnement')
            ->assertOk()->assertSee('Historique des paiements')->assertSee('150 000,00 XOF');

        $this->actingAs($this->user('staff', $c))->get('/admin/mon-abonnement')->assertForbidden();
    }

    public function test_consul_sees_own_subscription_status_on_dashboard(): void
    {
        $c = $this->consulate();

        $this->actingAs($this->user(User::ROLE_CONSULATE_ADMIN, $c))->get('/admin')
            ->assertOk()->assertSee('Abonnement à la plateforme');
    }
}

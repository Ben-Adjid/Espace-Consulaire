<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\Consulate;
use App\Models\News;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Accès par URL directe : un rôle ne doit pas atteindre une page interdite ni les données
 * d'un autre consulat en modifiant l'adresse (identifiant d'enregistrement ou chemin).
 */
class AdminUrlAccessTest extends TestCase
{
    use RefreshDatabase;

    private Consulate $a;

    private Consulate $b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->a = $this->consulate('tchad-senegal', 'TCH-SN', 'Sénégal');
        $this->b = $this->consulate('tchad-ghana', 'TCH-GH', 'Ghana');
    }

    private function consulate(string $slug, string $prefix, string $country): Consulate
    {
        return Consulate::create([
            'slug' => $slug, 'ref_prefix' => $prefix, 'name' => ['fr' => 'Tchad '.$country],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => $country],
            'covered_countries' => [$country], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
    }

    private function user(string $role, ?Consulate $consulate): User
    {
        $u = User::factory()->create(['is_active' => true, 'consulate_id' => $consulate?->id]);
        $u->assignRole($role);

        return $u;
    }

    public function test_staff_cannot_open_admin_only_pages_by_url(): void
    {
        $staff = $this->user('staff', $this->a);

        foreach (['/admin/users', '/admin/users/create', '/admin/consulates/create', '/admin/tableau-global', '/admin/nouveau-consulat'] as $url) {
            $this->actingAs($staff)->get($url)->assertForbidden();
        }
    }

    public function test_consul_cannot_open_another_consulate_records_by_id(): void
    {
        $consul = $this->user('consulate_admin', $this->a);
        $otherNews = News::withoutGlobalScopes()->create([
            'consulate_id' => $this->b->id, 'slug' => 'autre', 'title' => ['fr' => 'Actualité du Ghana'],
        ]);
        $otherCitizen = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->b->id, 'full_name' => 'Citoyen Ghana', 'email' => 'gh@example.test',
            'phone' => '+233 20 000 0000', 'password' => 'mot-de-passe-test', 'nationality' => 'Tchad',
            'residence_country' => 'Ghana', 'status' => Citizen::STATUS_ACTIVE,
        ]);

        $this->actingAs($consul)->get('/admin/news/'.$otherNews->id.'/edit')->assertNotFound();
        $this->actingAs($consul)->get('/admin/citizens/'.$otherCitizen->id.'/edit')->assertNotFound();
        $this->actingAs($consul)->get('/admin/consulates/'.$this->b->id.'/edit')->assertStatus(404);
        $this->actingAs($consul)->get('/admin/tableau-global')->assertForbidden();
    }

    public function test_receipt_of_another_consulate_is_forbidden(): void
    {
        $consul = $this->user('consulate_admin', $this->a);
        $payment = \App\Models\ConsulatePayment::create([
            'consulate_id' => $this->b->id, 'amount' => 100, 'currency' => 'EUR', 'paid_at' => now()->toDateString(),
            'period_start' => now()->toDateString(), 'period_end' => now()->addYear()->subDay()->toDateString(), 'method' => 'transfer',
        ]);

        $this->actingAs($consul)->get('/admin/paiements/'.$payment->id.'/recu')->assertForbidden();
    }

    public function test_citizen_token_is_refused_on_another_consulate(): void
    {
        $citizen = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->a->id, 'full_name' => 'Citoyen Sénégal', 'email' => 'sn@example.test',
            'phone' => '+221 77 000 00 00', 'password' => 'mot-de-passe-test', 'nationality' => 'Tchad',
            'residence_country' => 'Sénégal', 'status' => Citizen::STATUS_ACTIVE,
        ]);
        \Laravel\Sanctum\Sanctum::actingAs($citizen);

        $this->getJson('/api/v1/tchad-ghana/citizen/me')->assertStatus(403);
        $this->getJson('/api/v1/tchad-ghana/citizen/appointments')->assertStatus(403);
    }
}

<?php

namespace Tests\Feature;

use App\Actions\CreateConsulateFromTemplate;
use App\Mail\AdminInvitationMail;
use App\Models\Consulate;
use App\Models\ConsulatePayment;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Assistant de création : consulat, services, créneaux, premier paiement, activation et invitation du consul. */
class CreateConsulateTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Consulat du Tchad au Canada',
            'slug' => 'tchad-canada',
            'ref_prefix' => 'TCH-CA',
            'represented_country' => 'Tchad',
            'host_country' => 'Canada',
            'covered_countries' => ['Canada'],
            'languages' => ['fr'],
            'email' => 'contact@example.test',
            'phone' => null,
            'address' => null,
            'services' => ['passeport', 'visa'],
            'slots' => [
                ['weekdays' => [1, 2, 3, 4, 5], 'start_time' => '09:00', 'end_time' => '12:00', 'capacity' => 6],
            ],
            'payment' => [
                'amount' => 150000,
                'currency' => 'XOF',
                'paid_at' => now()->toDateString(),
                'period_start' => now()->toDateString(),
                'period_end' => now()->addYear()->subDay()->toDateString(),
                'method' => 'virement',
                'reference' => 'VIR-TEST-1',
            ],
            'consul_name' => 'Consul Test',
            'consul_email' => 'consul@example.test',
        ], $overrides);
    }

    public function test_creates_activated_consulate_with_first_payment_and_invited_consul(): void
    {
        Mail::fake();
        Role::firstOrCreate(['name' => User::ROLE_CONSULATE_ADMIN, 'guard_name' => 'web']);

        $consulate = app(CreateConsulateFromTemplate::class)($this->data(), null);

        $this->assertTrue($consulate->fresh()->is_active);
        $this->assertSame(now()->addYear()->subDay()->toDateString(), $consulate->fresh()->subscription_until->toDateString());
        $this->assertSame(2, Service::withoutGlobalScopes()->where('consulate_id', $consulate->id)->count());
        $this->assertSame(5, TimeSlot::withoutGlobalScopes()->where('consulate_id', $consulate->id)->count());

        $payment = ConsulatePayment::where('consulate_id', $consulate->id)->sole();
        $this->assertSame('VIR-TEST-1', $payment->reference);

        $consul = User::where('email', 'consul@example.test')->firstOrFail();
        $this->assertSame($consulate->id, $consul->consulate_id);
        $this->assertTrue($consul->hasRole(User::ROLE_CONSULATE_ADMIN));

        Mail::assertSent(AdminInvitationMail::class, fn (AdminInvitationMail $m) => $m->hasTo('consul@example.test'));
    }

    public function test_failed_creation_leaves_nothing_behind(): void
    {
        Mail::fake();
        Role::firstOrCreate(['name' => User::ROLE_CONSULATE_ADMIN, 'guard_name' => 'web']);

        // Un paiement invalide (montant absent) fait échouer la transaction : aucun consulat ne doit rester.
        $data = $this->data(['slug' => 'tchad-echec']);
        unset($data['payment']['amount']);

        try {
            app(CreateConsulateFromTemplate::class)($data, null);
            $this->fail('La création aurait dû échouer.');
        } catch (\Throwable) {
            // attendu
        }

        $this->assertFalse(Consulate::where('slug', 'tchad-echec')->exists());
        $this->assertFalse(User::where('email', 'consul@example.test')->exists());
    }
}

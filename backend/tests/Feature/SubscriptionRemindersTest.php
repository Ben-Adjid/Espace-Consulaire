<?php

namespace Tests\Feature;

use App\Actions\SendSubscriptionNotices;
use App\Mail\SubscriptionNotice;
use App\Models\Consulate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Rappels d'abonnement : une alerte par étape et par échéance, destinée aux consuls actifs. */
class SubscriptionRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([User::ROLE_CONSULATE_ADMIN, 'staff'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    private function consulate(array $overrides = []): Consulate
    {
        $c = Consulate::create(array_merge([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ], $overrides));

        $consul = User::factory()->create(['email' => 'consul@example.test', 'consulate_id' => $c->id, 'is_active' => true]);
        $consul->assignRole(User::ROLE_CONSULATE_ADMIN);

        return $c;
    }

    public function test_expiring_subscription_is_announced_once(): void
    {
        Mail::fake();
        $this->consulate(['subscription_until' => now()->addDays(10)->toDateString()]);

        app(SendSubscriptionNotices::class)();
        app(SendSubscriptionNotices::class)();

        Mail::assertSent(SubscriptionNotice::class, 1);
        Mail::assertSent(SubscriptionNotice::class, fn ($m) => $m->hasTo('consul@example.test') && $m->stage === 'warning');
    }

    public function test_each_stage_is_announced_when_it_is_reached(): void
    {
        Mail::fake();
        $c = $this->consulate(['subscription_until' => now()->addDays(10)->toDateString()]);
        app(SendSubscriptionNotices::class)();

        // Échéance dépassée : grâce.
        $c->forceFill(['subscription_until' => now()->subDays(3)->toDateString()])->save();
        app(SendSubscriptionNotices::class)();

        // Suspension effective (commande de contrôle à 02:00).
        $c->forceFill(['is_active' => false, 'suspended_for_nonpayment' => true])->save();
        app(SendSubscriptionNotices::class)();

        Mail::assertSent(SubscriptionNotice::class, 3);
        Mail::assertSent(SubscriptionNotice::class, fn ($m) => $m->stage === 'suspended');
    }

    public function test_a_new_payment_restarts_the_cycle(): void
    {
        Mail::fake();
        $c = $this->consulate(['subscription_until' => now()->addDays(10)->toDateString()]);
        app(SendSubscriptionNotices::class)();

        // Renouvellement : l'échéance passe au-delà du seuil d'alerte, aucune alerte.
        $c->forceFill(['subscription_until' => now()->addYear()->toDateString()])->save();
        app(SendSubscriptionNotices::class)();
        Mail::assertSent(SubscriptionNotice::class, 1);

        // Nouvelle échéance proche : l'alerte repart.
        $c->forceFill(['subscription_until' => now()->addDays(5)->toDateString()])->save();
        app(SendSubscriptionNotices::class)();
        Mail::assertSent(SubscriptionNotice::class, 2);
    }

    public function test_manually_deactivated_consulate_gets_no_grace_notice(): void
    {
        Mail::fake();
        $this->consulate(['is_active' => false, 'suspended_for_nonpayment' => false,
            'subscription_until' => now()->subDays(3)->toDateString()]);

        app(SendSubscriptionNotices::class)();

        Mail::assertNothingSent();
    }
}

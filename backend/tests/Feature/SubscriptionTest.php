<?php

namespace Tests\Feature;

use App\Models\Consulate;
use App\Models\ConsulatePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Abonnement annuel : paiements, période de grâce, suspension et réactivation automatiques. */
class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function consulate(array $attrs = []): Consulate
    {
        return Consulate::create(array_merge([
            'slug' => 'tchad-ghana', 'ref_prefix' => 'TCH-GH', 'name' => ['fr' => 'Tchad au Ghana'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Ghana'],
            'covered_countries' => ['Ghana'], 'languages' => ['fr'], 'is_active' => true,
        ], $attrs));
    }

    private function pay(Consulate $c, string $start, string $end): ConsulatePayment
    {
        return ConsulatePayment::create([
            'consulate_id' => $c->id, 'amount' => 500000, 'currency' => 'XOF', 'paid_at' => now()->toDateString(),
            'period_start' => $start, 'period_end' => $end, 'method' => 'virement',
        ]);
    }

    public function test_a_payment_sets_the_subscription_end_date(): void
    {
        $c = $this->consulate();
        $this->pay($c, '2026-01-01', '2026-12-31');

        $this->assertSame('2026-12-31', $c->fresh()->subscription_until->toDateString());
    }

    public function test_an_older_payment_never_shortens_the_subscription(): void
    {
        $c = $this->consulate(['subscription_until' => '2027-12-31']);
        $this->pay($c, '2026-01-01', '2026-12-31');

        $this->assertSame('2027-12-31', $c->fresh()->subscription_until->toDateString());
    }

    public function test_consulate_is_suspended_only_after_the_grace_period(): void
    {
        config(['subscription.grace_days' => 30]);
        Carbon::setTestNow('2027-01-20');
        $inGrace = $this->consulate(['slug' => 'tchad-ghana', 'subscription_until' => '2026-12-31']);
        $expired = $this->consulate(['slug' => 'tchad-togo', 'ref_prefix' => 'TCH-TG', 'subscription_until' => '2026-11-30']);

        $this->artisan('consulat:check-subscriptions')->assertSuccessful();

        $this->assertTrue($inGrace->fresh()->is_active);
        $this->assertFalse($expired->fresh()->is_active);
        $this->assertTrue($expired->fresh()->suspended_for_nonpayment);
        Carbon::setTestNow();
    }

    public function test_a_payment_reactivates_a_consulate_suspended_for_nonpayment(): void
    {
        $c = $this->consulate(['is_active' => false, 'suspended_for_nonpayment' => true, 'subscription_until' => '2025-12-31']);
        $this->pay($c, '2026-01-01', '2026-12-31');

        $fresh = $c->fresh();
        $this->assertTrue($fresh->is_active);
        $this->assertFalse($fresh->suspended_for_nonpayment);
    }

    public function test_a_payment_does_not_reactivate_a_consulate_deactivated_manually(): void
    {
        $c = $this->consulate(['is_active' => false, 'suspended_for_nonpayment' => false]);
        $this->pay($c, '2026-01-01', '2026-12-31');

        $this->assertFalse($c->fresh()->is_active);
    }

    public function test_a_consulate_without_subscription_is_not_suspended(): void
    {
        $c = $this->consulate();

        $this->artisan('consulat:check-subscriptions')->assertSuccessful();

        $this->assertTrue($c->fresh()->is_active);
        $this->assertSame('none', $c->fresh()->subscriptionState());
    }
}

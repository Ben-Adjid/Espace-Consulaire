<?php

namespace Tests\Unit;

use App\Models\Consulate;
use App\Services\SubscriptionPeriod;
use Carbon\CarbonImmutable;
use Tests\TestCase;

/** Unitaire : logique pure de l'abonnement (aucune écriture en base). */
class SubscriptionStateTest extends TestCase
{
    private function consulate(array $attributes): Consulate
    {
        $c = new Consulate();
        $c->forceFill(array_merge(['is_active' => true, 'suspended_for_nonpayment' => false], $attributes));

        return $c;
    }

    public function test_state_follows_the_subscription_dates(): void
    {
        $this->assertSame('none', $this->consulate(['subscription_until' => null])->subscriptionState());
        $this->assertSame('active', $this->consulate(['subscription_until' => now()->addDays(90)->toDateString()])->subscriptionState());
        $this->assertSame('expiring', $this->consulate(['subscription_until' => now()->addDays(10)->toDateString()])->subscriptionState());
        $this->assertSame('grace', $this->consulate(['subscription_until' => now()->subDays(5)->toDateString()])->subscriptionState());
    }

    public function test_suspension_flag_overrides_dates(): void
    {
        $c = $this->consulate(['subscription_until' => now()->addYear()->toDateString(), 'suspended_for_nonpayment' => true]);

        $this->assertSame('suspended', $c->subscriptionState());
    }

    public function test_next_period_is_exactly_one_year_minus_one_day(): void
    {
        $c = $this->consulate(['subscription_until' => null]);
        [$start, $end] = SubscriptionPeriod::nextFor($c);

        $this->assertSame(CarbonImmutable::today()->toDateString(), $start->toDateString());
        $this->assertSame($start->addYear()->subDay()->toDateString(), $end->toDateString());
    }
}

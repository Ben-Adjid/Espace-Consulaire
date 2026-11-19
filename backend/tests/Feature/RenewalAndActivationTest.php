<?php

namespace Tests\Feature;

use App\Actions\ActivateConsulate;
use App\Models\Consulate;
use App\Models\User;
use App\Services\SubscriptionPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Renouvellement (période suivante) et activation en un clic, avec leurs garde-fous. */
class RenewalAndActivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([User::ROLE_SUPER_ADMIN, User::ROLE_CONSULATE_ADMIN, 'staff'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    private function consulate(array $overrides = []): Consulate
    {
        return Consulate::create(array_merge([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ], $overrides));
    }

    public function test_next_period_starts_after_current_end_when_still_valid(): void
    {
        $c = $this->consulate(['subscription_until' => now()->addMonths(3)->toDateString()]);
        [$start, $end] = SubscriptionPeriod::nextFor($c);

        $this->assertSame(now()->addMonths(3)->addDay()->toDateString(), $start->toDateString());
        $this->assertSame($start->addYear()->subDay()->toDateString(), $end->toDateString());
    }

    public function test_next_period_starts_today_when_expired(): void
    {
        $c = $this->consulate(['subscription_until' => now()->subMonths(2)->toDateString()]);
        [$start] = SubscriptionPeriod::nextFor($c);

        $this->assertSame(CarbonImmutable::today()->toDateString(), $start->toDateString());
    }

    public function test_activation_refused_without_any_payment(): void
    {
        $c = $this->consulate(['is_active' => false]);

        $this->expectException(ValidationException::class);
        app(ActivateConsulate::class)($c);
    }

    public function test_activation_of_a_paid_consulate_lifts_suspension(): void
    {
        $c = $this->consulate(['is_active' => false, 'suspended_for_nonpayment' => true,
            'subscription_until' => now()->addYear()->toDateString()]);

        app(ActivateConsulate::class)($c);

        $c->refresh();
        $this->assertTrue($c->is_active);
        $this->assertFalse($c->suspended_for_nonpayment);
    }

    public function test_renewal_form_opens_prefilled_for_super_admin(): void
    {
        $c = $this->consulate(['subscription_until' => now()->addMonths(1)->toDateString()]);
        $super = User::factory()->create(['is_active' => true]);
        $super->assignRole(User::ROLE_SUPER_ADMIN);

        $this->actingAs($super)->get('/admin/consulate-payments/create?consulate_id='.$c->id)
            ->assertOk();
    }
}

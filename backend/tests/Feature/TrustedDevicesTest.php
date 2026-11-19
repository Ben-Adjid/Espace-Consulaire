<?php

namespace Tests\Feature;

use App\Models\Consulate;
use App\Models\ConsulatePayment;
use App\Models\User;
use App\Services\TrustedDevices;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Appareils de confiance : fin de la confiance = fin d'abonnement du consulat (ou renouvellement annuel pour le super admin), révocation, changement de mot de passe, expiration. */
class TrustedDevicesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    private function admin(): User
    {
        $u = User::factory()->create(['is_active' => true]);
        $u->assignRole('super_admin');
        $u->forceFill(['two_factor_secret' => 'secret-test', 'two_factor_confirmed_at' => now()])->save();

        return $u;
    }

    private function consulate(array $attrs = []): Consulate
    {
        return Consulate::create(array_merge([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ], $attrs));
    }

    public function test_the_super_admin_trust_has_no_automatic_renewal(): void
    {
        $user = $this->admin();
        $request = Request::create('/admin');
        $cookie = TrustedDevices::remember($user, $request);
        $request->cookies->set(TrustedDevices::COOKIE, $cookie->getValue());

        $this->assertNull(TrustedDevices::expiresAt($user));

        // Même des années plus tard, la confiance reste valide jusqu'à révocation manuelle.
        $this->travel(3)->years();
        $this->assertTrue(TrustedDevices::isTrusted($user, $request));
        $this->assertSame(0, TrustedDevices::purgeExpired());
        $this->travelBack();
    }

    public function test_a_consul_trust_lasts_until_the_end_of_that_consulate_subscription(): void
    {
        $c = $this->consulate(['subscription_until' => '2027-03-14']);
        $consul = User::factory()->create(['consulate_id' => $c->id]);
        $consul->assignRole('consulate_admin');

        $this->assertSame('2027-03-14', TrustedDevices::expiresAt($consul, CarbonImmutable::parse('2026-11-15 10:00'))->toDateString());
    }

    public function test_a_consulate_without_a_valid_subscription_gets_no_trust(): void
    {
        $c = $this->consulate(['subscription_until' => '2026-01-01']);
        $consul = User::factory()->create(['consulate_id' => $c->id]);
        $consul->assignRole('consulate_admin');

        $expires = TrustedDevices::expiresAt($consul, CarbonImmutable::parse('2026-11-15 10:00'));
        $this->assertTrue($expires->lte(CarbonImmutable::parse('2026-11-15 10:00')));
    }

    public function test_a_recorded_renewal_forgets_the_devices_of_that_consulate(): void
    {
        $c = $this->consulate();
        $consul = User::factory()->create(['consulate_id' => $c->id]);
        $consul->assignRole('consulate_admin');
        TrustedDevices::remember($consul, Request::create('/admin'));
        $this->assertSame(1, DB::table('two_factor_trusted_devices')->count());

        ConsulatePayment::create([
            'consulate_id' => $c->id, 'amount' => 500000, 'currency' => 'XOF', 'paid_at' => now()->toDateString(),
            'period_start' => now()->toDateString(), 'period_end' => now()->addYear()->subDay()->toDateString(), 'method' => 'virement',
        ]);

        $this->assertSame(0, DB::table('two_factor_trusted_devices')->count());
    }

    public function test_a_remembered_browser_is_trusted_until_revoked(): void
    {
        $user = $this->admin();
        $request = Request::create('/admin', 'GET', [], [], [], ['HTTP_USER_AGENT' => 'Navigateur test']);

        $cookie = TrustedDevices::remember($user, $request);
        $request->cookies->set(TrustedDevices::COOKIE, $cookie->getValue());

        $this->assertTrue(TrustedDevices::isTrusted($user, $request));
        $this->assertSame(1, TrustedDevices::forgetAll($user));
        $this->assertFalse(TrustedDevices::isTrusted($user, $request));
    }

    public function test_changing_the_password_forgets_every_trusted_device(): void
    {
        $user = $this->admin();
        $request = Request::create('/admin');
        $cookie = TrustedDevices::remember($user, $request);
        $request->cookies->set(TrustedDevices::COOKIE, $cookie->getValue());

        $user->update(['password' => 'un-nouveau-mot-de-passe-solide']);

        $this->assertFalse(TrustedDevices::isTrusted($user, $request));
    }

    public function test_an_expired_device_is_not_trusted(): void
    {
        $user = $this->admin();
        $request = Request::create('/admin');
        $cookie = TrustedDevices::remember($user, $request);
        $request->cookies->set(TrustedDevices::COOKIE, $cookie->getValue());
        DB::table('two_factor_trusted_devices')->update(['expires_at' => now()->subDay()]);

        $this->assertFalse(TrustedDevices::isTrusted($user, $request));
        $this->assertSame(1, TrustedDevices::purgeExpired());
    }

    public function test_a_trusted_browser_skips_the_code_but_still_needs_the_password(): void
    {
        $user = $this->admin();
        $request = Request::create('/admin');
        $cookie = TrustedDevices::remember($user, $request);

        // Sans cookie : redirection vers la vérification à 6 chiffres.
        $this->actingAs($user)->get('/admin')->assertRedirect(\App\Filament\Admin\Pages\TwoFactorChallenge::getUrl());

        // Avec l'appareil de confiance : pas de nouvelle vérification.
        $response = $this->actingAs($user)->withCookie(TrustedDevices::COOKIE, $cookie->getValue())->get('/admin');
        $this->assertFalse(str_contains((string) $response->headers->get('Location'), 'two-factor-challenge'));
    }
}

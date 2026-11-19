<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\TwoFactorChallenge;
use App\Filament\Admin\Pages\TwoFactorSettings;
use App\Models\User;
use App\Services\TwoFactor;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/** Double authentification du personnel (TOTP). */
class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(bool $withTwoFactor): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::create(['name' => 'Super', 'email' => 'super@example.test', 'password' => 'mot-de-passe-long', 'is_active' => true]);
        $user->assignRole('super_admin');
        if ($withTwoFactor) {
            $user->forceFill([
                'two_factor_secret' => (new Google2FA())->generateSecretKey(32),
                'two_factor_recovery_codes' => ['AAAAA-BBBBB'],
                'two_factor_confirmed_at' => now(),
            ])->save();
        }

        return $user;
    }

    public function test_a_valid_code_is_accepted_and_a_wrong_one_refused(): void
    {
        $secret = (new Google2FA())->generateSecretKey(32);
        $code = (new Google2FA())->getCurrentOtp($secret);

        $this->assertTrue(app(TwoFactor::class)->verify($secret, $code));
        $this->assertFalse(app(TwoFactor::class)->verify($secret, '000000') && $code !== '000000');
        $this->assertFalse(app(TwoFactor::class)->verify($secret, 'abc'));
    }

    public function test_the_secret_is_encrypted_in_the_database(): void
    {
        $user = $this->superAdmin(true);
        $raw = \DB::table('users')->where('id', $user->id)->value('two_factor_secret');

        $this->assertNotSame($user->two_factor_secret, $raw);
    }

    public function test_admin_with_two_factor_must_enter_the_code_before_the_dashboard(): void
    {
        $user = $this->superAdmin(true);

        $this->actingAs($user)->get('/admin')->assertRedirect(TwoFactorChallenge::getUrl());
        $this->actingAs($user)->withSession(['two_factor_passed' => $user->id])->get('/admin')->assertOk();
    }

    public function test_role_requiring_two_factor_is_sent_to_activation(): void
    {
        config(['security.two_factor_required_roles' => 'super_admin']);
        $user = $this->superAdmin(false);

        $this->actingAs($user)->get('/admin')->assertRedirect(TwoFactorSettings::getUrl());
        $this->actingAs($user)->get(TwoFactorSettings::getUrl())->assertOk();
    }

    public function test_recovery_code_works_only_once(): void
    {
        $user = $this->superAdmin(true);
        $service = app(TwoFactor::class);

        $this->assertTrue($service->consumeRecoveryCode($user, 'aaaaa-bbbbb'));
        $this->assertFalse($service->consumeRecoveryCode($user->fresh(), 'AAAAA-BBBBB'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Récupération : un opérateur peut désactiver la double authentification d'un compte bloqué. */
class ResetTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_operator_can_disable_two_factor_for_a_locked_out_account(): void
    {
        $user = User::factory()->create(['email' => 'bloque@consulat.test']);
        $user->forceFill(['two_factor_secret' => 'secret', 'two_factor_recovery_codes' => ['A1B2'], 'two_factor_confirmed_at' => now()])->save();
        $this->assertTrue($user->hasTwoFactorEnabled());

        $this->artisan('consulat:reset-two-factor', ['email' => 'bloque@consulat.test', '--force' => true])->assertSuccessful();

        $user->refresh();
        $this->assertFalse($user->hasTwoFactorEnabled());
        $this->assertNull($user->two_factor_recovery_codes);
    }

    public function test_an_unknown_account_is_reported(): void
    {
        $this->artisan('consulat:reset-two-factor', ['email' => 'inconnu@consulat.test', '--force' => true])->assertFailed();
    }
}

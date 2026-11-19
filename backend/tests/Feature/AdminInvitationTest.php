<?php

namespace Tests\Feature;

use App\Actions\SendAdminInvitation;
use App\Mail\AdminInvitationMail;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Activation du compte du consul par email : lien signé vers la page de choix du mot de passe. */
class AdminInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitation_sends_a_signed_activation_link(): void
    {
        Mail::fake();
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::create(['name' => 'Consul Togo', 'email' => 'consul@example.test', 'password' => bcrypt(str()->random(40)), 'is_active' => true]);

        app(SendAdminInvitation::class)->execute($user);

        Mail::assertSent(AdminInvitationMail::class, function (AdminInvitationMail $mail) {
            return $mail->hasTo('consul@example.test')
                && str_contains($mail->url, '/admin/password-reset/reset')
                && str_contains($mail->url, 'signature=');
        });
    }

    public function test_admin_forgot_password_page_is_available(): void
    {
        $this->get('/admin/password-reset/request')->assertOk();
    }
}

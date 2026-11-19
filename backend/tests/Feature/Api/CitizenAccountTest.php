<?php

namespace Tests\Feature\Api;

use App\Mail\CitizenLinkMail;
use App\Models\Citizen;
use App\Models\Consulate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Parcours du compte citoyen : inscription, confirmation de l'email, validation de résidence, connexion, réinitialisation. */
class CitizenAccountTest extends TestCase
{
    use RefreshDatabase;

    private Consulate $chad;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->chad = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr', 'en', 'ar'], 'is_active' => true,
        ]);
    }

    private function register(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/v1/tchad-senegal/citizen/register', array_merge([
            'full_name' => 'Amina Test', 'email' => 'amina@example.test', 'phone' => '+221 77 000 00 00',
            'password' => 'mot-de-passe-test', 'password_confirmation' => 'mot-de-passe-test',
            'residence_country' => 'Sénégal', 'nationality' => true, 'consent' => true,
        ], $overrides));
    }

    /** Compte vérifié et activé (raccourci pour les tests de connexion). */
    private function activeCitizen(string $email = 'amina@example.test'): Citizen
    {
        return Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->chad->id, 'full_name' => 'Amina Test', 'email' => $email,
            'phone' => '+221 77 000 00 00', 'password' => 'mot-de-passe-test', 'nationality' => 'Tchad',
            'residence_country' => 'Sénégal', 'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
    }

    public function test_registration_creates_a_pending_account_and_sends_verification_and_receipt(): void
    {
        $this->register()->assertCreated()->assertJsonPath('status', 'pending');

        $citizen = Citizen::withoutGlobalScopes()->first();
        $this->assertSame(Citizen::STATUS_PENDING, $citizen->status);
        $this->assertSame($this->chad->id, $citizen->consulate_id);
        $this->assertNull($citizen->email_verified_at);
        $this->assertNotNull($citizen->email_verification_token);
        Mail::assertSent(CitizenLinkMail::class, fn ($m) => $m->purpose === 'verify');
    }

    public function test_unverified_account_cannot_log_in(): void
    {
        $this->register()->assertCreated();

        $this->postJson('/api/v1/tchad-senegal/citizen/login', ['email' => 'amina@example.test', 'password' => 'mot-de-passe-test'])
            ->assertForbidden()
            ->assertJsonPath('status', 'unverified');
    }

    public function test_email_confirmation_link_works_once(): void
    {
        $this->register()->assertCreated();
        $citizen = Citizen::withoutGlobalScopes()->first();
        $token = $citizen->issueEmailVerificationToken();

        $this->postJson('/api/v1/tchad-senegal/citizen/verify-email', ['token' => $token, 'email' => 'amina@example.test'])
            ->assertOk();
        $this->assertNotNull($citizen->fresh()->email_verified_at);

        $this->postJson('/api/v1/tchad-senegal/citizen/verify-email', ['token' => $token, 'email' => 'amina@example.test'])
            ->assertUnprocessable();
    }

    public function test_pending_verified_account_cannot_log_in(): void
    {
        $this->register()->assertCreated();
        Citizen::withoutGlobalScopes()->first()->update(['email_verified_at' => now()]);

        $this->postJson('/api/v1/tchad-senegal/citizen/login', ['email' => 'amina@example.test', 'password' => 'mot-de-passe-test'])
            ->assertForbidden()
            ->assertJsonPath('status', 'pending');
    }

    public function test_active_account_logs_in_and_reads_its_profile(): void
    {
        $this->activeCitizen();

        $token = $this->postJson('/api/v1/tchad-senegal/citizen/login', ['email' => 'amina@example.test', 'password' => 'mot-de-passe-test'])
            ->assertOk()->json('token');

        $this->withToken($token)->getJson('/api/v1/tchad-senegal/citizen/me')
            ->assertOk()->assertJsonPath('data.status', 'active');
    }

    public function test_wrong_password_is_refused(): void
    {
        $this->activeCitizen();

        $this->postJson('/api/v1/tchad-senegal/citizen/login', ['email' => 'amina@example.test', 'password' => 'mauvais-mot'])
            ->assertUnprocessable();
    }

    public function test_registration_is_refused_for_an_uncovered_residence(): void
    {
        $this->register(['residence_country' => 'Ghana'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('residence_country');
    }

    public function test_booking_requires_an_account(): void
    {
        $this->postJson('/api/v1/tchad-senegal/appointments', [
            'service' => 'passeport', 'date' => '2026-11-23', 'slot_id' => 1, 'full_name' => 'Anonyme',
            'email' => 'anon@example.test', 'phone' => '+221 77 000 00 00', 'consent' => true,
            'nationality' => true, 'residence_country' => 'Sénégal',
        ])->assertUnauthorized();
    }

    public function test_rejected_account_shows_the_reason(): void
    {
        $this->activeCitizen()->update(['status' => Citizen::STATUS_REJECTED, 'rejection_reason' => 'Justificatif non conforme']);

        $this->postJson('/api/v1/tchad-senegal/citizen/login', ['email' => 'amina@example.test', 'password' => 'mot-de-passe-test'])
            ->assertForbidden()
            ->assertJsonPath('reason', 'Justificatif non conforme');
    }

    public function test_forgot_password_sends_a_link_and_answers_the_same_for_unknown_emails(): void
    {
        $this->activeCitizen();

        $this->postJson('/api/v1/tchad-senegal/citizen/forgot-password', ['email' => 'amina@example.test'])->assertOk();
        Mail::assertSent(CitizenLinkMail::class, fn ($m) => $m->purpose === 'password');

        $this->postJson('/api/v1/tchad-senegal/citizen/forgot-password', ['email' => 'inconnu@example.test'])->assertOk();
    }

    public function test_password_can_be_reset_with_the_emailed_token(): void
    {
        $citizen = $this->activeCitizen();
        $token = Password::broker('citizens')->createToken($citizen);

        $this->postJson('/api/v1/tchad-senegal/citizen/reset-password', [
            'token' => $token, 'email' => 'amina@example.test',
            'password' => 'nouveau-mot-de-passe', 'password_confirmation' => 'nouveau-mot-de-passe',
        ])->assertOk();

        $this->postJson('/api/v1/tchad-senegal/citizen/login', ['email' => 'amina@example.test', 'password' => 'nouveau-mot-de-passe'])
            ->assertOk();
    }

    public function test_reset_is_refused_with_a_wrong_token(): void
    {
        $citizen = $this->activeCitizen();
        Password::broker('citizens')->createToken($citizen);

        $this->postJson('/api/v1/tchad-senegal/citizen/reset-password', [
            'token' => 'faux-jeton', 'email' => 'amina@example.test',
            'password' => 'nouveau-mot-de-passe', 'password_confirmation' => 'nouveau-mot-de-passe',
        ])->assertUnprocessable();
    }

    public function test_citizen_cannot_use_an_account_of_another_consulate(): void
    {
        $other = Consulate::create([
            'slug' => 'ghana-senegal', 'ref_prefix' => 'GHA-SN', 'name' => ['fr' => 'Ghana'],
            'represented_country' => ['fr' => 'Ghana'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ]);
        $citizen = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $other->id, 'full_name' => 'Ghanéen', 'email' => 'gh@example.test', 'phone' => '+221 77 000 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Ghana', 'residence_country' => 'Sénégal',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
        Sanctum::actingAs($citizen);

        $this->getJson('/api/v1/tchad-senegal/citizen/me')->assertForbidden();
    }
}

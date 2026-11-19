<?php

namespace Tests\Feature\Api;

use App\Models\Citizen;
use App\Models\Consulate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/** Verrouillage temporaire après plusieurs mots de passe erronés. */
class CitizenLockoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('citizen-login:1:lock@example.test');
        $consulate = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ]);
        Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $consulate->id, 'full_name' => 'Verrou', 'email' => 'lock@example.test', 'phone' => '+235 66 00 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Sénégal',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
    }

    public function test_account_is_locked_after_five_wrong_passwords_even_with_the_right_one_afterwards(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/tchad-senegal/citizen/login', ['email' => 'lock@example.test', 'password' => 'faux-mot'])
                ->assertUnprocessable();
        }

        $this->postJson('/api/v1/tchad-senegal/citizen/login', ['email' => 'lock@example.test', 'password' => 'mot-de-passe-test'])
            ->assertStatus(429)
            ->assertJsonStructure(['message', 'retry_after']);
    }

    public function test_successful_login_before_the_limit_resets_the_counter(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->postJson('/api/v1/tchad-senegal/citizen/login', ['email' => 'lock@example.test', 'password' => 'faux-mot']);
        }
        $this->postJson('/api/v1/tchad-senegal/citizen/login', ['email' => 'lock@example.test', 'password' => 'mot-de-passe-test'])
            ->assertOk();

        $this->postJson('/api/v1/tchad-senegal/citizen/login', ['email' => 'lock@example.test', 'password' => 'faux-mot'])
            ->assertUnprocessable();
    }
}

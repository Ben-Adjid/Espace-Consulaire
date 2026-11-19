<?php

namespace Tests\Feature;

use App\Models\Consulate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Limites de débit : formulaire de réservation (10/min par IP) et connexion citoyen (verrouillage après 5 échecs). */
class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    private function consulate(): void
    {
        Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ]);
    }

    public function test_booking_form_is_throttled_per_ip(): void
    {
        $this->consulate();

        // Les 10 premières tentatives passent la limite (puis échouent à la validation).
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/tchad-senegal/appointments', [])->assertStatus(422);
        }

        $this->postJson('/api/v1/tchad-senegal/appointments', [])->assertStatus(429);
    }

    public function test_citizen_login_locks_after_five_failed_attempts(): void
    {
        $this->consulate();
        $payload = ['email' => 'inconnu@example.test', 'password' => 'mauvais-mot-de-passe'];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/tchad-senegal/citizen/login', $payload)->assertStatus(422);
        }

        $this->postJson('/api/v1/tchad-senegal/citizen/login', $payload)->assertStatus(429);
    }
}

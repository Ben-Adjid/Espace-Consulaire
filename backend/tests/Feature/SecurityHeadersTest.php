<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Sécurité : pas de version de PHP exposée, en-têtes de base présents sur l'API. */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_does_not_reveal_php_version_and_sends_base_headers(): void
    {
        $response = $this->getJson('/api/v1/consulates');

        $response->assertOk();
        $this->assertFalse($response->headers->has('X-Powered-By'));
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}

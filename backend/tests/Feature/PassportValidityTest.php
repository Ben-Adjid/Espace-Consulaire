<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\Consulate;
use App\Models\PreRequest;
use App\Models\RequestDocument;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Visa : le passeport doit rester valable au moins N mois (paramètre du service), sinon l'envoi est refusé. */
class PassportValidityTest extends TestCase
{
    use RefreshDatabase;

    private function setup2(?int $months): array
    {
        $c = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ]);
        $service = Service::withoutGlobalScopes()->create(['consulate_id' => $c->id, 'slug' => 'visa', 'name' => ['fr' => 'Visa'], 'is_active' => true, 'allow_appointment' => true, 'min_passport_validity_months' => $months]);
        $citizen = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $c->id, 'full_name' => 'Amina', 'email' => 'amina@example.test', 'phone' => '+221 77 000 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Sénégal', 'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
        $pre = PreRequest::withoutGlobalScopes()->create(['consulate_id' => $c->id, 'citizen_id' => $citizen->id, 'service_id' => $service->id, 'status' => 'draft', 'data' => []]);

        return [$citizen, $pre];
    }

    private function passport(PreRequest $pre, \Carbon\CarbonInterface $expires): void
    {
        $pre->documents()->forceCreate(['label' => 'Passeport', 'stored_path' => 'pre-requests/p', 'original_name' => 'p.pdf', 'mime' => 'application/pdf', 'size' => 10, 'sha256' => str_repeat('a', 64), 'status' => 'pending', 'expires_on' => $expires]);
    }

    public function test_a_visa_refuses_a_passport_expiring_too_soon(): void
    {
        [$citizen, $pre] = $this->setup2(6);
        $this->passport($pre, now()->addMonths(3));
        Sanctum::actingAs($citizen);

        $this->postJson("/api/v1/tchad-senegal/citizen/requests/{$pre->id}/submit")->assertUnprocessable();
        $this->assertSame('draft', $pre->refresh()->status);
    }

    public function test_a_visa_accepts_a_passport_valid_long_enough(): void
    {
        [$citizen, $pre] = $this->setup2(6);
        $this->passport($pre, now()->addMonths(12));
        Sanctum::actingAs($citizen);

        $this->postJson("/api/v1/tchad-senegal/citizen/requests/{$pre->id}/submit")->assertOk()->assertJsonPath('data.status', 'submitted');
    }

    public function test_a_service_without_a_rule_does_not_check_the_passport(): void
    {
        [$citizen, $pre] = $this->setup2(null);
        Sanctum::actingAs($citizen);
        $pre->documents()->forceCreate(['label' => 'Acte', 'stored_path' => 'pre-requests/a', 'original_name' => 'a.pdf', 'mime' => 'application/pdf', 'size' => 10, 'sha256' => str_repeat('b', 64), 'status' => 'pending']);

        $this->postJson("/api/v1/tchad-senegal/citizen/requests/{$pre->id}/submit")->assertOk();
    }
}

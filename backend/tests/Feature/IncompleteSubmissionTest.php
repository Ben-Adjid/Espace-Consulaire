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

/** Un dossier incomplet ne peut pas être soumis : les pièces exigées par le service doivent toutes être déposées. */
class IncompleteSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_file_missing_required_documents_cannot_be_submitted(): void
    {
        $c = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ]);
        $service = Service::withoutGlobalScopes()->create([
            'consulate_id' => $c->id, 'slug' => 'passeport', 'name' => ['fr' => 'Passeport'], 'is_active' => true, 'allow_appointment' => true,
            'required_documents' => ['fr' => "Acte de naissance\nPhoto d’identité"],
        ]);
        $citizen = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $c->id, 'full_name' => 'Amina', 'email' => 'amina@example.test', 'phone' => '+221 77 000 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Sénégal',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
        $pre = PreRequest::withoutGlobalScopes()->create([
            'consulate_id' => $c->id, 'citizen_id' => $citizen->id, 'service_id' => $service->id, 'status' => 'draft', 'data' => [],
        ]);
        $pre->documents()->forceCreate([
            'label' => 'Acte de naissance', 'stored_path' => 'pre-requests/a', 'original_name' => 'a.pdf', 'mime' => 'application/pdf',
            'size' => 10, 'sha256' => str_repeat('a', 64), 'status' => 'pending',
        ]);

        Sanctum::actingAs($citizen);
        $this->postJson("/api/v1/tchad-senegal/citizen/requests/{$pre->id}/submit")
            ->assertUnprocessable()
            ->assertJsonPath('missing_documents.0', 'Photo d’identité');
        $this->assertSame('draft', $pre->refresh()->status);

        $pre->documents()->create(['label' => 'Photo d’identité', 'stored_path' => 'pre-requests/b', 'original_name' => 'b.pdf', 'mime' => 'application/pdf', 'size' => 10, 'sha256' => str_repeat('b', 64), 'status' => 'pending']);
        $this->postJson("/api/v1/tchad-senegal/citizen/requests/{$pre->id}/submit")->assertOk()->assertJsonPath('data.status', 'submitted');
    }
}

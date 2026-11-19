<?php

namespace Tests\Feature\Api;

use App\Models\Citizen;
use App\Models\Consulate;
use App\Models\PreRequest;
use App\Models\RequestDocument;
use App\Models\Service;
use App\Services\DocumentStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Pré-demande en ligne : brouillon, pièces privées, soumission, cloisonnement entre citoyens. */
class PreRequestTest extends TestCase
{
    use RefreshDatabase;

    private Consulate $c;

    private Citizen $citizen;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(RequestDocument::DISK);
        $this->c = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ]);
        $this->service = Service::withoutGlobalScopes()->create(['consulate_id' => $this->c->id, 'slug' => 'passeport', 'name' => ['fr' => 'Passeport'], 'is_active' => true, 'allow_appointment' => true]);
        $this->citizen = $this->activeCitizen('a@example.test');
    }

    private function activeCitizen(string $email): Citizen
    {
        return Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->c->id, 'full_name' => 'Citoyen', 'email' => $email, 'phone' => '+235 66 00 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Sénégal',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('acte.pdf', "%PDF-1.4\n1 0 obj << >> endobj\n%%EOF");
    }

    public function test_full_flow_draft_documents_then_submission(): void
    {
        Sanctum::actingAs($this->citizen);

        $id = $this->postJson('/api/v1/tchad-senegal/citizen/requests', ['service' => 'passeport'])
            ->assertCreated()->assertJsonPath('data.status', 'draft')->json('data.id');

        $this->putJson("/api/v1/tchad-senegal/citizen/requests/{$id}", ['data' => ['nom_pere' => 'Mahamat']])->assertOk();
        $this->postJson("/api/v1/tchad-senegal/citizen/requests/{$id}/documents", ['label' => 'Acte de naissance', 'file' => $this->pdf()])
            ->assertCreated()->assertJsonPath('data.status', 'pending');
        $this->postJson("/api/v1/tchad-senegal/citizen/requests/{$id}/submit")->assertOk()
            ->assertJsonPath('data.status', 'submitted');

        $this->putJson("/api/v1/tchad-senegal/citizen/requests/{$id}", ['data' => ['nom_pere' => 'Autre']])->assertUnprocessable();
    }

    public function test_submission_needs_at_least_one_document(): void
    {
        Sanctum::actingAs($this->citizen);
        $id = $this->postJson('/api/v1/tchad-senegal/citizen/requests', ['service' => 'passeport'])->json('data.id');

        $this->postJson("/api/v1/tchad-senegal/citizen/requests/{$id}/submit")->assertUnprocessable();
    }

    public function test_a_disguised_file_is_refused(): void
    {
        Sanctum::actingAs($this->citizen);
        $id = $this->postJson('/api/v1/tchad-senegal/citizen/requests', ['service' => 'passeport'])->json('data.id');

        // Un script renommé en PDF : le contenu réel est refusé, pas seulement l'extension.
        $fake = UploadedFile::fake()->createWithContent('acte.pdf', '<?php echo "x"; ?>');
        $this->postJson("/api/v1/tchad-senegal/citizen/requests/{$id}/documents", ['label' => 'Acte', 'file' => $fake])
            ->assertUnprocessable();
    }

    public function test_documents_are_stored_privately_under_a_random_name(): void
    {
        Sanctum::actingAs($this->citizen);
        $id = $this->postJson('/api/v1/tchad-senegal/citizen/requests', ['service' => 'passeport'])->json('data.id');
        $this->postJson("/api/v1/tchad-senegal/citizen/requests/{$id}/documents", ['label' => 'Acte de naissance', 'file' => $this->pdf()])->assertCreated();

        $doc = RequestDocument::first();
        $this->assertStringStartsWith('pre-requests/', $doc->stored_path);
        $this->assertStringNotContainsString('acte', $doc->stored_path);
        Storage::disk(RequestDocument::DISK)->assertExists($doc->stored_path);
        $this->assertTrue(app(DocumentStorage::class)->isIntact($doc));
    }

    public function test_tampered_document_is_detected(): void
    {
        Sanctum::actingAs($this->citizen);
        $id = $this->postJson('/api/v1/tchad-senegal/citizen/requests', ['service' => 'passeport'])->json('data.id');
        $this->postJson("/api/v1/tchad-senegal/citizen/requests/{$id}/documents", ['label' => 'Acte', 'file' => $this->pdf()])->assertCreated();
        $doc = RequestDocument::first();

        Storage::disk(RequestDocument::DISK)->put($doc->stored_path, '%PDF-1.4 altéré');

        $this->assertFalse(app(DocumentStorage::class)->isIntact($doc));
    }

    public function test_another_citizen_cannot_see_or_submit_a_request(): void
    {
        Sanctum::actingAs($this->citizen);
        $id = $this->postJson('/api/v1/tchad-senegal/citizen/requests', ['service' => 'passeport'])->json('data.id');

        Sanctum::actingAs($this->activeCitizen('b@example.test'));
        $this->getJson("/api/v1/tchad-senegal/citizen/requests/{$id}")->assertNotFound();
        $this->postJson("/api/v1/tchad-senegal/citizen/requests/{$id}/submit")->assertNotFound();
    }

    public function test_pre_request_requires_an_account(): void
    {
        $this->postJson('/api/v1/tchad-senegal/citizen/requests', ['service' => 'passeport'])->assertUnauthorized();
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\Association;
use App\Models\Consulate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Association des étudiants : seules les informations publiques sortent de l'API. */
class AssociationTest extends TestCase
{
    use RefreshDatabase;

    private Consulate $consulate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->consulate = Consulate::create([
            'slug' => 'tchad-togo', 'ref_prefix' => 'TCH-TG', 'name' => ['fr' => 'Tchad au Togo'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Togo'],
            'covered_countries' => ['Togo'], 'languages' => ['fr'], 'is_active' => true,
        ]);
    }

    private function association(array $attrs = []): Association
    {
        $a = Association::withoutGlobalScopes()->create(array_merge([
            'consulate_id' => $this->consulate->id, 'name' => 'Association des étudiants tchadiens au Togo',
            'acronym' => 'AETT', 'is_recognized' => true, 'is_public' => true,
        ], $attrs));
        $a->officers()->create(['role' => 'president', 'full_name' => 'Présidente en exercice', 'mandate_end' => now()->addYear()]);
        $a->officers()->create(['role' => 'treasurer', 'full_name' => 'Ancien trésorier', 'mandate_end' => now()->subYear()]);
        $a->members()->create(['full_name' => 'Étudiant Confidentiel', 'email' => 'secret@example.test']);

        return $a;
    }

    public function test_public_association_shows_the_current_bureau_only(): void
    {
        $this->association();

        $response = $this->getJson('/api/v1/tchad-togo/association')->assertOk()
            ->assertJsonPath('data.acronym', 'AETT')
            ->assertJsonCount(1, 'data.officers')
            ->assertJsonPath('data.officers.0.full_name', 'Présidente en exercice');

        // La liste des membres et leurs coordonnées ne sont jamais publiées.
        $this->assertStringNotContainsString('Étudiant Confidentiel', $response->getContent());
        $this->assertStringNotContainsString('secret@example.test', $response->getContent());
    }

    public function test_non_public_association_is_not_exposed(): void
    {
        $this->association(['is_public' => false]);

        $this->getJson('/api/v1/tchad-togo/association')->assertNotFound();
    }

    public function test_consulate_without_association_answers_not_found(): void
    {
        $this->getJson('/api/v1/tchad-togo/association')->assertNotFound();
    }
}

<?php

namespace Tests\Feature;

use App\Actions\RecordElection;
use App\Models\Association;
use App\Models\AssociationActivity;
use App\Models\AssociationArchive;
use App\Models\AssociationOfficer;
use App\Models\Consulate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Élections (renouvellement du bureau), activités et archives publiées de l'association. */
class AssociationExtrasTest extends TestCase
{
    use RefreshDatabase;

    private function association(): Association
    {
        $c = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ]);

        return Association::withoutGlobalScopes()->create([
            'consulate_id' => $c->id, 'name' => 'Association test', 'is_public' => true,
        ]);
    }

    public function test_election_replaces_board_and_closes_previous_mandates(): void
    {
        $assoc = $this->association();
        app(RecordElection::class)($assoc, 2026, '2026-03-01', [
            ['role' => 'president', 'full_name' => 'Ancien Président'],
        ]);

        app(RecordElection::class)($assoc, 2027, '2027-03-01', [
            ['role' => 'president', 'full_name' => 'Nouveau Président', 'school' => 'Université'],
            ['role' => 'treasurer', 'full_name' => 'Trésorière'],
        ]);

        $old = AssociationOfficer::withoutGlobalScopes()->where('full_name', 'Ancien Président')->first();
        $this->assertSame('2027-02-28', $old->mandate_end->toDateString());

        $current = AssociationOfficer::withoutGlobalScopes()->where('association_id', $assoc->id)
            ->whereNull('mandate_end')->get();
        $this->assertCount(2, $current);
        $this->assertSame(['Nouveau Président', 'Trésorière'], $current->pluck('full_name')->sort()->values()->all());
    }

    public function test_one_election_per_year(): void
    {
        $assoc = $this->association();
        app(RecordElection::class)($assoc, 2026, '2026-03-01', [['role' => 'president', 'full_name' => 'A']]);

        $this->expectException(ValidationException::class);
        app(RecordElection::class)($assoc, 2026, '2026-06-01', [['role' => 'president', 'full_name' => 'B']]);
    }

    public function test_public_page_shows_only_published_activities_and_archives(): void
    {
        $assoc = $this->association();
        AssociationActivity::create(['association_id' => $assoc->id, 'title' => 'Journée d’accueil', 'starts_on' => '2026-09-10', 'is_public' => true]);
        AssociationActivity::create(['association_id' => $assoc->id, 'title' => 'Brouillon interne', 'starts_on' => '2026-09-11', 'is_public' => false]);
        AssociationArchive::create(['association_id' => $assoc->id, 'person_name' => 'Personne publiée', 'title' => 'Ancien ministre', 'is_public' => true]);
        AssociationArchive::create(['association_id' => $assoc->id, 'person_name' => 'Personne cachée', 'is_public' => false]);

        $response = $this->getJson('/api/v1/tchad-senegal/association')->assertOk();
        $data = $response->json('data');

        $this->assertSame(['Journée d’accueil'], array_column($data['activities'], 'title'));
        $this->assertSame(['Personne publiée'], array_column($data['archives'], 'person_name'));
        $this->assertArrayNotHasKey('members', $data);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\AssociationElection;
use App\Models\Consulate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Scellé : n'est produit qu'après proclamation, et son empreinte couvre le contenu. */
class ElectionSealTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_seal_is_refused_before_proclamation_and_written_after(): void
    {
        $c = Consulate::create([
            'slug' => 'tchad-togo', 'ref_prefix' => 'TCH-TG', 'name' => ['fr' => 'Tchad au Togo'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Togo'],
            'covered_countries' => ['Togo'], 'languages' => ['fr'], 'is_active' => true,
        ]);
        $association = Association::withoutGlobalScopes()->create(['consulate_id' => $c->id, 'name' => 'AETT', 'is_public' => true]);
        $e = AssociationElection::create([
            'association_id' => $association->id, 'year' => 2026, 'held_on' => now()->toDateString(),
            'voting_opens_at' => now()->subDays(10), 'voting_closes_at' => now()->subDays(3), 'second_round_hours' => 72,
        ]);

        $this->artisan('consulat:seal-election', ['election' => $e->id, '--file' => sys_get_temp_dir().'/seal-test.txt'])->assertFailed();

        $e->update(['proclaimed_at' => now()]);
        $file = sys_get_temp_dir().'/seal-test-ok.txt';
        $this->artisan('consulat:seal-election', ['election' => $e->id, '--file' => $file])->assertSuccessful();
        $this->assertStringContainsString('SCELLÉ DU SCRUTIN', (string) file_get_contents($file));
        $this->assertStringContainsString('Empreinte du scellé', (string) file_get_contents($file));
    }
}

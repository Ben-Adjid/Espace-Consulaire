<?php

namespace Tests\Feature;

use App\Actions\CreatePartialElection;
use App\Actions\ProcessElection;
use App\Models\Association;
use App\Models\AssociationElectorate;
use App\Models\AssociationMember;
use App\Models\AssociationOfficer;
use App\Models\Citizen;
use App\Models\Consulate;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Vote du bureau pour un poste resté vacant : électorat = bureau en exercice uniquement. */
class AssociationBoardVoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_board_vote_is_open_only_to_the_sitting_board(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $c = Consulate::create([
            'slug' => 'tchad-togo', 'ref_prefix' => 'TCH-TG', 'name' => ['fr' => 'Tchad au Togo'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Togo'],
            'covered_countries' => ['Togo'], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
        $association = Association::withoutGlobalScopes()->create(['consulate_id' => $c->id, 'name' => 'AETT', 'is_public' => true]);

        $make = fn (string $email) => Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $c->id, 'full_name' => $email, 'email' => $email, 'phone' => '+228 90 00 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Togo',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
        $officer = $make('membre-bureau@example.test');
        $simple = $make('simple-adherent@example.test');
        foreach ([$officer, $simple] as $p) {
            AssociationMember::create(['association_id' => $association->id, 'citizen_id' => $p->id, 'full_name' => $p->full_name, 'status' => 'active', 'category' => 'student', 'joined_at' => now()->subYear()]);
        }
        AssociationOfficer::create(['association_id' => $association->id, 'role' => 'president', 'full_name' => 'Membre', 'citizen_id' => $officer->id, 'mandate_start' => now()->subMonth()]);

        $parent = \App\Models\AssociationElection::create([
            'association_id' => $association->id, 'year' => 2026, 'held_on' => now()->toDateString(),
            'voting_opens_at' => now()->subDays(20), 'voting_closes_at' => now()->subDays(13), 'second_round_hours' => 72,
        ]);
        $board = app(CreatePartialElection::class)($parent, ['secretary'], 'board');

        $this->assertSame('board', $board->electorate);

        // Huit jours plus tard : la candidature est close, le vote est ouvert ; la liste est figée.
        $this->travel(8)->days();
        app(ProcessElection::class)($board->refresh());

        $voters = AssociationElectorate::where('election_id', $board->id)->pluck('citizen_id')->all();
        $this->assertSame([$officer->id], $voters);
    }
}

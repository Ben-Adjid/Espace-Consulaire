<?php

namespace Tests\Feature;

use App\Actions\ProcessElection;
use App\Mail\ElectionConvocationMail;
use App\Models\Association;
use App\Models\AssociationElection;
use App\Models\AssociationMember;
use App\Models\Citizen;
use App\Models\Consulate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** À l'ouverture du vote, chaque électeur de la liste reçoit une convocation ; les non-électeurs n'en reçoivent pas. */
class ElectionConvocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_elector_is_invited_when_voting_opens(): void
    {
        Mail::fake();
        $c = Consulate::create([
            'slug' => 'tchad-togo', 'ref_prefix' => 'TCH-TG', 'name' => ['fr' => 'Tchad au Togo'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Togo'],
            'covered_countries' => ['Togo'], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
        $association = Association::withoutGlobalScopes()->create(['consulate_id' => $c->id, 'name' => 'AETT', 'is_public' => true]);
        $member = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $c->id, 'full_name' => 'Membre', 'email' => 'membre@example.test', 'phone' => '+228 90 00 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Togo',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
        Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $c->id, 'full_name' => 'Hors liste', 'email' => 'hors@example.test', 'phone' => '+228 90 00 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Togo',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
        AssociationMember::create(['association_id' => $association->id, 'citizen_id' => $member->id, 'full_name' => 'Membre', 'status' => 'active', 'category' => 'student', 'joined_at' => now()->subYear()]);
        $e = AssociationElection::create([
            'association_id' => $association->id, 'year' => 2026, 'held_on' => now()->toDateString(),
            'voting_opens_at' => now()->subHour(), 'voting_closes_at' => now()->addDay(), 'second_round_hours' => 72,
        ]);

        app(ProcessElection::class)($e->refresh());

        Mail::assertSent(ElectionConvocationMail::class, 1);
        Mail::assertSent(ElectionConvocationMail::class, fn ($m) => $m->hasTo('membre@example.test'));
    }
}

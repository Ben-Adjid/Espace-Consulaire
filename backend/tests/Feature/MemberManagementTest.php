<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\AssociationBoardLog;
use App\Models\AssociationMember;
use App\Models\AssociationOfficer;
use App\Models\Citizen;
use App\Models\Consulate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Gestion des adhérents : président seul ; les contacts ne sont jamais renvoyés. */
class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = '/api/v1/mm-test/citizen/association/members';

    private Association $association;

    private Citizen $president;

    private Citizen $secretary;

    protected function setUp(): void
    {
        parent::setUp();
        $c = Consulate::create([
            'slug' => 'mm-test', 'ref_prefix' => 'MMT', 'name' => ['fr' => 'MM'],
            'represented_country' => ['fr' => 'Test'], 'host_country' => ['fr' => 'Test'],
            'covered_countries' => ['Test'], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
        $this->association = Association::withoutGlobalScopes()->create(['consulate_id' => $c->id, 'name' => 'AEMM', 'is_public' => true]);
        $make = fn (string $email) => Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $c->id, 'full_name' => $email, 'email' => $email, 'phone' => '+000 00 00 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Test', 'residence_country' => 'Test',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
        $this->president = $make('president@example.test');
        $this->secretary = $make('secretaire@example.test');
        AssociationOfficer::create(['association_id' => $this->association->id, 'role' => 'president', 'full_name' => 'Président', 'citizen_id' => $this->president->id, 'mandate_start' => now()->subMonth(), 'mandate_end' => now()->addYear()]);
        AssociationOfficer::create(['association_id' => $this->association->id, 'role' => 'secretary', 'full_name' => 'Secrétaire', 'citizen_id' => $this->secretary->id, 'mandate_start' => now()->subMonth(), 'mandate_end' => now()->addYear()]);
    }

    public function test_president_adds_a_member_without_exposing_contacts(): void
    {
        Sanctum::actingAs($this->president);
        $this->postJson(self::BASE, [
            'full_name' => 'Nouvel Adhérent', 'category' => 'student', 'email' => 'nouveau@example.test', 'phone' => '+000 99 99 99 99',
        ])->assertCreated();

        $member = AssociationMember::where('full_name', 'Nouvel Adhérent')->firstOrFail();
        $this->assertSame('nouveau@example.test', $member->email);
        $this->assertTrue(AssociationBoardLog::where('action', 'member_added')->exists());

        $json = $this->getJson(str_replace('/members', '/members', self::BASE))->assertOk()->json();
        $this->assertStringNotContainsString('nouveau@example.test', json_encode($json));
    }

    public function test_president_deactivates_a_member(): void
    {
        $member = AssociationMember::create(['association_id' => $this->association->id, 'full_name' => 'Adhérent Deux', 'status' => 'active', 'category' => 'alumni', 'joined_at' => now()->subYear()]);

        Sanctum::actingAs($this->president);
        $this->patchJson(self::BASE.'/'.$member->id, ['status' => 'inactive'])->assertOk();

        $this->assertSame('inactive', $member->refresh()->status);
        $this->assertTrue(AssociationBoardLog::where('action', 'member_deactivated')->exists());
    }

    public function test_other_board_members_cannot_add_or_edit_members(): void
    {
        $member = AssociationMember::create(['association_id' => $this->association->id, 'full_name' => 'Adhérent Trois', 'status' => 'active', 'category' => 'student']);

        Sanctum::actingAs($this->secretary);
        $this->postJson(self::BASE, ['full_name' => 'Intrus', 'category' => 'student'])->assertForbidden();
        $this->patchJson(self::BASE.'/'.$member->id, ['status' => 'inactive'])->assertForbidden();

        $this->assertSame('active', $member->refresh()->status);
    }

    public function test_a_member_of_another_association_cannot_be_edited(): void
    {
        $autre = Consulate::create(['slug' => 'mm-autre', 'ref_prefix' => 'MMA', 'name' => ['fr' => 'Autre'], 'represented_country' => ['fr' => 'Test'], 'host_country' => ['fr' => 'Test'], 'covered_countries' => ['Test'], 'languages' => ['fr'], 'is_active' => true, 'subscription_until' => now()->addYear()->toDateString()]);
        $other = Association::withoutGlobalScopes()->create(['consulate_id' => $autre->id, 'name' => 'AUTRE', 'is_public' => true]);
        $foreign = AssociationMember::create(['association_id' => $other->id, 'full_name' => 'Étranger', 'status' => 'active', 'category' => 'student']);

        Sanctum::actingAs($this->president);
        $this->patchJson(self::BASE.'/'.$foreign->id, ['status' => 'inactive'])->assertNotFound();
    }
}

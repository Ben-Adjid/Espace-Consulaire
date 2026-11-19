<?php

namespace Tests\Feature;

use App\Actions\CloseAssociationBoard;
use App\Actions\RecordElection;
use App\Mail\AssociationRoleMail;
use App\Mail\CitizenLinkMail;
use App\Mail\EmailChangeNoticeMail;
use App\Models\Association;
use App\Models\Citizen;
use App\Models\Consulate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Espace citoyen : téléphone, changement d'email confirmé, et rôle associatif (bureau, fin de mandat). */
class CitizenProfileAndAssociationRolesTest extends TestCase
{
    use RefreshDatabase;

    private Consulate $consulate;

    private Citizen $citizen;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->consulate = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ]);
        $this->citizen = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->consulate->id, 'full_name' => 'Amina Test', 'email' => 'amina@example.test',
            'phone' => '+221 77 000 00 00', 'password' => 'mot-de-passe-test', 'nationality' => 'Tchad',
            'residence_country' => 'Sénégal', 'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
    }

    public function test_phone_is_updated_immediately(): void
    {
        Sanctum::actingAs($this->citizen);

        $this->patchJson('/api/v1/tchad-senegal/citizen/profile', ['phone' => '+221 70 111 22 33'])
            ->assertOk()
            ->assertJsonPath('data.phone', '+221 70 111 22 33');

        $this->assertSame('+221 70 111 22 33', $this->citizen->fresh()->phone);
    }

    public function test_email_change_needs_current_password_and_confirmation(): void
    {
        Sanctum::actingAs($this->citizen);

        $this->patchJson('/api/v1/tchad-senegal/citizen/profile', [
            'email' => 'nouvelle@example.test', 'current_password' => 'mauvais-mot-de-passe',
        ])->assertStatus(422);
        $this->assertSame('amina@example.test', $this->citizen->fresh()->email);

        $this->patchJson('/api/v1/tchad-senegal/citizen/profile', [
            'email' => 'nouvelle@example.test', 'current_password' => 'mot-de-passe-test',
        ])->assertOk();

        $fresh = $this->citizen->fresh();
        $this->assertSame('amina@example.test', $fresh->email, 'L’email ne change qu’après confirmation.');
        $this->assertSame('nouvelle@example.test', $fresh->pending_email);
        Mail::assertSent(CitizenLinkMail::class, fn ($m) => $m->hasTo('nouvelle@example.test'));
        Mail::assertSent(EmailChangeNoticeMail::class, fn ($m) => $m->hasTo('amina@example.test'));
    }

    public function test_email_change_is_applied_with_the_token_from_the_link(): void
    {
        Sanctum::actingAs($this->citizen);
        $token = Str::random(48);
        $this->citizen->forceFill([
            'pending_email' => 'nouvelle@example.test',
            'email_verification_token' => hash('sha256', $token),
        ])->save();

        $this->postJson('/api/v1/tchad-senegal/citizen/email/confirm', ['token' => 'x'.str_repeat('a', 47)])
            ->assertStatus(422);

        $this->postJson('/api/v1/tchad-senegal/citizen/email/confirm', ['token' => $token])->assertOk();

        $fresh = $this->citizen->fresh();
        $this->assertSame('nouvelle@example.test', $fresh->email);
        $this->assertNull($fresh->pending_email);
        $this->assertNull($fresh->email_verification_token);
    }

    public function test_email_already_used_by_another_citizen_is_refused(): void
    {
        Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->consulate->id, 'full_name' => 'Autre', 'email' => 'prise@example.test',
            'phone' => '+221 70 000 00 00', 'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Sénégal',
            'status' => Citizen::STATUS_ACTIVE,
        ]);
        Sanctum::actingAs($this->citizen);

        $this->patchJson('/api/v1/tchad-senegal/citizen/profile', [
            'email' => 'prise@example.test', 'current_password' => 'mot-de-passe-test',
        ])->assertStatus(422);
    }

    public function test_elected_president_sees_role_and_it_ends_with_the_mandate(): void
    {
        $assoc = Association::withoutGlobalScopes()->create([
            'consulate_id' => $this->consulate->id, 'name' => 'Association des étudiants', 'is_public' => true,
        ]);

        // Mandat ouvert (sans date de fin) : le rôle est affiché dans l'espace citoyen.
        app(RecordElection::class)($assoc, 2026, now()->subMonth()->toDateString(), [
            ['role' => 'president', 'full_name' => 'Amina Test', 'citizen_id' => $this->citizen->id],
        ]);

        Sanctum::actingAs($this->citizen);
        $this->getJson('/api/v1/tchad-senegal/citizen/me')
            ->assertOk()
            ->assertJsonPath('data.association.current.0.role', 'Président(e)')
            ->assertJsonPath('data.association.current.0.association', 'Association des étudiants');

        Mail::assertSent(AssociationRoleMail::class, fn ($m) => $m->kind === 'appointed' && $m->hasTo('amina@example.test'));

        // Nouvelle élection : le mandat précédent se termine la veille, le citoyen redevient simple citoyen.
        app(RecordElection::class)($assoc, 2027, now()->toDateString(), [
            ['role' => 'treasurer', 'full_name' => 'Autre Personne'],
        ]);

        $status = $this->citizen->fresh()->associationStatus();
        $this->assertSame([], $status['current']);
        $this->assertSame('Président(e)', $status['past'][0]['role']);
        $this->assertSame(now()->subDay()->toDateString(), $status['past'][0]['mandate_end']);

        Mail::assertSent(AssociationRoleMail::class, fn ($m) => $m->kind === 'ended' && $m->hasTo('amina@example.test'));
    }

    public function test_closing_the_board_archives_mandates_and_notifies(): void
    {
        $assoc = Association::withoutGlobalScopes()->create([
            'consulate_id' => $this->consulate->id, 'name' => 'Association test', 'is_public' => true,
        ]);
        app(RecordElection::class)($assoc, 2026, '2026-03-01', [
            ['role' => 'secretary', 'full_name' => 'Amina Test', 'citizen_id' => $this->citizen->id],
        ]);

        $count = app(CloseAssociationBoard::class)($assoc, '2026-11-12');

        $this->assertSame(1, $count);
        $this->assertSame([], $this->citizen->fresh()->associationStatus()['current']);
        Mail::assertSent(AssociationRoleMail::class, fn ($m) => $m->kind === 'ended');
        $this->assertTrue(Hash::check('mot-de-passe-test', $this->citizen->fresh()->password));
    }
}

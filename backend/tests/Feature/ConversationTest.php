<?php

namespace Tests\Feature;

use App\Actions\PostConversationMessage;
use App\Filament\Admin\Resources\ConversationResource;
use App\Mail\ConversationMessageMail;
use App\Models\Citizen;
use App\Models\Consulate;
use App\Models\Conversation;
use App\Models\PreRequest;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

/** Messagerie citoyen ↔ consulat : cloisonnement, envoi, clôture, notification, pièces hors périmètre. */
class ConversationTest extends TestCase
{
    use RefreshDatabase;

    private Consulate $a;

    private Consulate $b;

    private Citizen $citizen;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->a = $this->consulate('tchad-senegal', 'TCH-SN', 'Sénégal');
        $this->b = $this->consulate('tchad-ghana', 'TCH-GH', 'Ghana');
        $this->citizen = $this->citizenOf($this->a, 'a@example.test');
        $this->staff = $this->staffOf($this->a);
    }

    private function consulate(string $slug, string $prefix, string $country): Consulate
    {
        return Consulate::create([
            'slug' => $slug, 'ref_prefix' => $prefix, 'name' => ['fr' => 'Tchad '.$country],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => $country],
            'covered_countries' => [$country], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
    }

    private function citizenOf(Consulate $c, string $email, string $locale = 'fr'): Citizen
    {
        return Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $c->id, 'full_name' => 'Citoyen', 'email' => $email, 'phone' => '+235 66 00 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Sénégal',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(), 'locale' => $locale,
        ]);
    }

    private function staffOf(Consulate $c): User
    {
        $u = User::factory()->create(['is_active' => true, 'consulate_id' => $c->id]);
        $u->assignRole('staff');

        return $u;
    }

    private function conversation(?Citizen $citizen = null, string $status = 'open'): Conversation
    {
        $citizen ??= $this->citizen;
        $conv = app(PostConversationMessage::class)->start($citizen, 'Question sur mon dossier', 'Bonjour, où en est-il ?');
        $conv->update(['status' => $status]);

        return $conv->refresh();
    }

    public function test_citizen_starts_a_conversation_and_sees_only_their_own(): void
    {
        Sanctum::actingAs($this->citizen);
        $id = $this->postJson('/api/v1/tchad-senegal/citizen/messages', ['subject' => 'Rendez-vous', 'body' => 'Puis-je changer ?'])
            ->assertCreated()->assertJsonPath('data.can_reply', true)->json('data.id');

        $other = $this->citizenOf($this->a, 'b@example.test');
        Conversation::withoutGlobalScopes()->create(['consulate_id' => $this->a->id, 'citizen_id' => $other->id, 'subject' => 'Privé', 'status' => 'open']);

        $list = $this->getJson('/api/v1/tchad-senegal/citizen/messages')->assertOk();
        $this->assertCount(1, $list->json('data'));
        $this->assertSame(['Rendez-vous'], array_column($list->json('data'), 'subject'));

        $this->getJson("/api/v1/tchad-senegal/citizen/messages/{$id}")->assertOk();
        Sanctum::actingAs($other);
        $this->getJson("/api/v1/tchad-senegal/citizen/messages/{$id}")->assertNotFound();
        $this->postJson("/api/v1/tchad-senegal/citizen/messages/{$id}/reply", ['body' => 'Intrusion'])->assertNotFound();
    }

    public function test_citizen_message_to_another_consulate_is_refused(): void
    {
        $foreign = $this->citizenOf($this->b, 'gh@example.test');
        Sanctum::actingAs($foreign);

        $this->getJson('/api/v1/tchad-senegal/citizen/messages')->assertForbidden();
    }

    public function test_staff_reply_is_signed_consulate_and_notifies_the_citizen_in_their_language(): void
    {
        $english = $this->citizenOf($this->a, 'en@example.test', 'en');
        $conv = $this->conversation($english);

        app(PostConversationMessage::class)->reply($conv, 'Merci, pièce reçue.', $this->staff);

        $conv->refresh();
        $this->assertTrue($conv->unread_by_citizen);
        $this->assertFalse($conv->unread_by_staff);
        Mail::assertSent(ConversationMessageMail::class, fn ($m) => $m->hasTo('en@example.test') && $m->locale === 'en');

        Sanctum::actingAs($english);
        $thread = $this->getJson("/api/v1/tchad-senegal/citizen/messages/{$conv->id}")->assertOk()->json('data');
        $this->assertFalse(collect($this->getJson('/api/v1/tchad-senegal/citizen/messages')->json('data'))->firstWhere('id', $conv->id)['unread']);
        $this->assertSame('consulate', $thread['messages'][1]['by']);
        $this->assertStringNotContainsString($this->staff->name, json_encode($thread));
    }

    public function test_citizen_cannot_write_in_a_closed_conversation_but_staff_reopens_it(): void
    {
        $conv = $this->conversation(status: 'closed');

        try {
            app(PostConversationMessage::class)->reply($conv, 'Encore une question', null);
            $this->fail('Une conversation clôturée ne doit pas recevoir de message du citoyen.');
        } catch (ValidationException) {
            $this->assertSame(1, $conv->messages()->count());
        }

        app(PostConversationMessage::class)->reply($conv, 'Nous la rouvrons.', $this->staff);
        $this->assertSame('open', $conv->refresh()->status);
    }

    public function test_staff_of_another_consulate_cannot_reply(): void
    {
        $conv = $this->conversation();
        $outsider = $this->staffOf($this->b);

        $this->expectException(ValidationException::class);
        app(PostConversationMessage::class)->reply($conv, 'Hors périmètre', $outsider);
    }

    public function test_citizen_is_limited_in_open_conversations_and_message_length(): void
    {
        for ($i = 0; $i < Conversation::MAX_OPEN_PER_CITIZEN; $i++) {
            $this->conversation();
        }

        try {
            app(PostConversationMessage::class)->start($this->citizen, 'Encore', 'Un message');
            $this->fail('La limite de conversations ouvertes doit s’appliquer.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        Sanctum::actingAs($this->citizen);
        $this->postJson('/api/v1/tchad-senegal/citizen/messages', ['subject' => 'Long', 'body' => str_repeat('a', PostConversationMessage::MAX_BODY + 1)])
            ->assertUnprocessable();
    }

    public function test_a_message_linked_to_someone_elses_request_is_refused(): void
    {
        $service = Service::withoutGlobalScopes()->create(['consulate_id' => $this->a->id, 'slug' => 'passeport', 'name' => ['fr' => 'Passeport'], 'is_active' => true, 'allow_appointment' => true]);
        $otherCitizen = $this->citizenOf($this->a, 'other@example.test');
        $foreignRequest = PreRequest::withoutGlobalScopes()->create(['consulate_id' => $this->a->id, 'citizen_id' => $otherCitizen->id, 'service_id' => $service->id, 'status' => 'submitted', 'submitted_at' => now()]);

        $this->expectException(ValidationException::class);
        app(PostConversationMessage::class)->start($this->citizen, 'Mon dossier', 'Bonjour', $foreignRequest);
    }

    public function test_admin_list_and_thread_respect_consulate_and_permissions(): void
    {
        $conv = $this->conversation();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->actingAs($this->staff)->get('/admin/conversations')->assertOk()->assertSee('Question sur mon dossier');
        $this->actingAs($this->staff)->get('/admin/conversations/'.$conv->id)->assertOk()->assertSee('Bonjour, où en est-il ?');

        $outsider = $this->staffOf($this->b);
        $this->actingAs($outsider)->get('/admin/conversations/'.$conv->id)->assertNotFound();

        $this->assertFalse(ConversationResource::canCreate());
    }

    public function test_opening_the_thread_marks_it_read_for_staff(): void
    {
        $conv = $this->conversation();
        $this->assertTrue($conv->refresh()->unread_by_staff);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->actingAs($this->staff)->get('/admin/conversations/'.$conv->id)->assertOk();

        $this->assertFalse($conv->refresh()->unread_by_staff);
    }

    public function test_staff_reply_from_the_admin_page_is_stored_and_notifies(): void
    {
        $conv = $this->conversation();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->actingAs($this->staff);
        Livewire::test(\App\Filament\Admin\Resources\ConversationResource\Pages\ViewConversation::class, ['record' => $conv->id])
            ->callAction('reply', ['body' => 'Votre dossier avance.'])
            ->assertHasNoActionErrors()->assertNotified(__('Réponse envoyée'));

        // Le dernier message : relation déjà triée par id croissant, on inverse le tri (latest() ne l'annulerait pas).
        $this->assertSame('staff', $conv->messages()->reorder('id', 'desc')->first()->sender);
        $this->assertTrue($conv->refresh()->unread_by_citizen);
        Mail::assertSent(ConversationMessageMail::class);
    }

    public function test_consul_writes_to_a_citizen_from_a_pre_request(): void
    {
        $service = Service::withoutGlobalScopes()->create(['consulate_id' => $this->a->id, 'slug' => 'passeport', 'name' => ['fr' => 'Passeport'], 'is_active' => true, 'allow_appointment' => true]);
        $pre = PreRequest::withoutGlobalScopes()->create(['consulate_id' => $this->a->id, 'citizen_id' => $this->citizen->id, 'service_id' => $service->id, 'status' => 'submitted', 'submitted_at' => now()]);

        $conv = app(PostConversationMessage::class)->start($this->citizen, 'Pré-demande #'.$pre->id, 'Merci de compléter.', $pre, $this->staff);

        $this->assertSame($pre->id, $conv->pre_request_id);
        $this->assertSame('staff', $conv->messages()->first()->sender);
        Mail::assertSent(ConversationMessageMail::class, fn ($m) => $m->hasTo($this->citizen->email));
    }
}

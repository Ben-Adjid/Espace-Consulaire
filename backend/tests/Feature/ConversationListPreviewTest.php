<?php

namespace Tests\Feature;

use App\Actions\PostConversationMessage;
use App\Models\Citizen;
use App\Models\Consulate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** La liste de la messagerie affiche le DERNIER message (et non le premier) et le compte des non-lus. */
class ConversationListPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_preview_shows_the_latest_message_and_unread_count(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $c = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
        $citizen = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $c->id, 'full_name' => 'Citoyen', 'email' => 'p@example.test', 'phone' => '+235 66 00 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Sénégal',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
        $staff = User::factory()->create(['is_active' => true, 'consulate_id' => $c->id]);
        $staff->assignRole('staff');

        $conv = app(PostConversationMessage::class)->start($citizen, 'Sujet', 'Premier message');
        app(PostConversationMessage::class)->reply($conv, 'Dernier message du consulat', $staff);

        Sanctum::actingAs($citizen);
        $list = $this->getJson('/api/v1/tchad-senegal/citizen/messages')->assertOk();

        $this->assertSame('Dernier message du consulat', $list->json('data.0.last_excerpt'));
        $this->assertSame('consulate', $list->json('data.0.last_by'));
        $this->assertSame(1, $list->json('meta.unread'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Consulate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Administration en français, anglais et arabe : choix mémorisé par utilisateur, arabe de droite à gauche. */
class AdminLocaleTest extends TestCase
{
    use RefreshDatabase;

    private function consul(string $locale = 'fr'): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $c = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Consulat du Tchad au Sénégal', 'en' => 'Consulate of Chad in Senegal', 'ar' => 'قنصلية تشاد في السنغال'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr', 'en', 'ar'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
        $u = User::factory()->create(['is_active' => true, 'consulate_id' => $c->id, 'locale' => $locale]);
        $u->assignRole('consulate_admin');

        return $u;
    }

    public function test_french_is_the_default(): void
    {
        $html = $this->actingAs($this->consul())->get('/admin')->assertOk()->getContent();
        $this->assertStringContainsString('dir="ltr"', $html);
        $this->assertStringContainsString('Accueil du public', $html);
    }

    public function test_arabic_dashboard_is_right_to_left_and_translated(): void
    {
        $html = $this->actingAs($this->consul('ar'))->get('/admin')->assertOk()->getContent();
        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString('استقبال الجمهور', $html);   // groupe « Accueil du public »
        $this->assertStringContainsString('الطلبات المسبقة', $html);   // « Pré-demandes »
        $this->assertStringContainsString('قنصلية تشاد في السنغال', $html); // nom du consulat dans la langue
        $this->assertStringNotContainsString('Dossiers et citoyens', $html);
    }

    public function test_english_pages_and_resources_are_translated(): void
    {
        $user = $this->consul('en');
        $this->actingAs($user)->get('/admin')->assertOk()->assertSee('Applications and citizens');
        $this->actingAs($user)->get('/admin/appointments')->assertOk()->assertSee('Public reception');
    }

    public function test_switching_language_is_remembered_for_the_user(): void
    {
        $user = $this->consul();

        $this->actingAs($user)->get('/admin/langue/ar')->assertRedirect();
        $this->assertSame('ar', $user->fresh()->locale);

        // Nouvelle session (reconnexion) : la préférence enregistrée s'applique.
        $this->flushSession();
        $this->actingAs($user->fresh())->get('/admin')->assertSee('dir="rtl"', false);
    }

    public function test_unknown_language_is_refused(): void
    {
        $this->actingAs($this->consul())->get('/admin/langue/de')->assertNotFound();
    }

    public function test_login_page_follows_the_chosen_language(): void
    {
        $this->get('/admin/langue/en')->assertRedirect();
        $this->get('/admin/login')->assertOk()->assertSee('Staff and consul area');
    }
}

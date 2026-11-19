<?php

namespace Tests\Feature;

use App\Actions\ReviewPreRequest;
use App\Mail\AdminInvitationMail;
use App\Mail\CitizenRegistered;
use App\Mail\PreRequestDecisionMail;
use App\Mail\SubscriptionNotice;
use App\Models\Citizen;
use App\Models\Consulate;
use App\Models\PreRequest;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Les emails partent dans la langue du destinataire (citoyen : langue de son dernier usage ; agent : langue d'administration). */
class EmailLocaleTest extends TestCase
{
    use RefreshDatabase;

    private Consulate $c;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->c = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN',
            'name' => ['fr' => 'Consulat du Tchad au Sénégal', 'en' => 'Consulate of Chad in Senegal', 'ar' => 'قنصلية تشاد في السنغال'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr', 'en', 'ar'], 'is_active' => true,
            'subscription_until' => now()->addDays(10)->toDateString(),
        ]);
    }

    private function citizen(string $locale): Citizen
    {
        return Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->c->id, 'full_name' => 'Amina', 'email' => "amina.{$locale}@example.test", 'locale' => $locale,
            'phone' => '+221 77 000 00 00', 'password' => 'mot-de-passe-test', 'nationality' => 'Tchad',
            'residence_country' => 'Sénégal', 'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
    }

    public function test_registration_records_the_site_language_and_mails_in_it(): void
    {
        $this->postJson('/api/v1/tchad-senegal/citizen/register?lang=en', [
            'full_name' => 'John Test', 'email' => 'john@example.test', 'phone' => '+221 77 000 00 00',
            'password' => 'mot-de-passe-test', 'password_confirmation' => 'mot-de-passe-test',
            'residence_country' => 'Sénégal', 'nationality' => true, 'consent' => true,
        ])->assertSuccessful();

        $this->assertSame('en', Citizen::withoutGlobalScopes()->where('email', 'john@example.test')->value('locale'));
        Mail::assertSent(CitizenRegistered::class, function ($m) {
            $html = $m->render();

            return $m->locale === 'en' && str_contains($html, 'Hello John Test,') && str_contains($html, 'We have received your account request');
        });
    }

    public function test_logging_in_in_another_language_updates_the_preference(): void
    {
        $citizen = $this->citizen('fr');
        $this->postJson('/api/v1/tchad-senegal/citizen/login?lang=ar', ['email' => $citizen->email, 'password' => 'mot-de-passe-test'])->assertOk();

        $this->assertSame('ar', $citizen->fresh()->locale);
    }

    public function test_arabic_citizen_receives_the_decision_in_arabic_right_to_left(): void
    {
        $citizen = $this->citizen('ar');
        $service = Service::withoutGlobalScopes()->create(['consulate_id' => $this->c->id, 'slug' => 'passeport', 'name' => ['fr' => 'Passeport', 'ar' => 'جواز السفر'], 'is_active' => true, 'allow_appointment' => true]);
        $staff = User::factory()->create(['consulate_id' => $this->c->id]);
        $request = PreRequest::withoutGlobalScopes()->create(['consulate_id' => $this->c->id, 'citizen_id' => $citizen->id, 'service_id' => $service->id, 'status' => 'submitted', 'submitted_at' => now()]);

        app(ReviewPreRequest::class)($request, 'validated', null, $staff);

        Mail::assertSent(PreRequestDecisionMail::class, function ($m) {
            $html = $m->render();

            return $m->locale === 'ar' && str_contains($html, 'dir="rtl"') && str_contains($html, 'مرحبًا Amina')
                && str_contains($html, 'مقبول') && ! str_contains($html, 'Bonjour');
        });
    }

    public function test_french_remains_the_default(): void
    {
        $citizen = $this->citizen('fr');
        $service = Service::withoutGlobalScopes()->create(['consulate_id' => $this->c->id, 'slug' => 'visa', 'name' => ['fr' => 'Visa'], 'is_active' => true, 'allow_appointment' => true]);
        $mail = new PreRequestDecisionMail(PreRequest::withoutGlobalScopes()->create(['consulate_id' => $this->c->id, 'citizen_id' => $citizen->id, 'service_id' => $service->id, 'status' => 'validated']), $citizen);

        $this->assertStringContainsString('Bonjour Amina,', $mail->render());
    }

    public function test_staff_invitation_and_subscription_notice_follow_the_admin_language(): void
    {
        $user = User::factory()->create(['consulate_id' => $this->c->id, 'name' => 'Agent', 'locale' => 'en']);
        $mail = new AdminInvitationMail($user, 'https://example.test/activate');
        $this->assertStringContainsString('An administration account has been created for you', $mail->render());

        $consul = User::factory()->create(['consulate_id' => $this->c->id, 'is_active' => true, 'locale' => 'ar']);
        $consul->assignRole('consulate_admin');
        app(\App\Actions\SendSubscriptionNotices::class)();

        Mail::assertSent(SubscriptionNotice::class, fn ($m) => $m->hasTo($consul->email) && $m->locale === 'ar' && str_contains($m->render(), 'قنصلية تشاد في السنغال'));
    }
}

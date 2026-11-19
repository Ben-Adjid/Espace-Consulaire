<?php

namespace Tests\Feature;

use App\Actions\AssignPreRequest;
use App\Actions\ReviewPreRequest;
use App\Filament\Admin\Resources\ResponseTemplateResource;
use App\Filament\Admin\Resources\StaffAbsenceResource;
use App\Filament\Admin\Widgets\TodayWorkWidget;
use App\Models\Citizen;
use App\Models\Consulate;
use App\Models\PreRequest;
use App\Models\ResponseTemplate;
use App\Models\Service;
use App\Models\StaffAbsence;
use App\Models\User;
use App\Services\GlobalDashboard;
use App\Services\MonthlyReport;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Gestion des dossiers (priorités 1 et 2) : attribution, délais et retards, historique, pièces manquantes,
 * modèles de réponses, absences, calendrier, rapport imprimable et comparaison entre consulats.
 */
class CaseManagementTest extends TestCase
{
    use RefreshDatabase;

    private Consulate $a;

    private Consulate $b;

    private Service $service;

    private User $staff;

    private User $consul;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->a = $this->consulate('tchad-senegal', 'TCH-SN', 'Sénégal');
        $this->b = $this->consulate('tchad-ghana', 'TCH-GH', 'Ghana');
        $this->service = Service::withoutGlobalScopes()->create([
            'consulate_id' => $this->a->id, 'slug' => 'passeport', 'name' => ['fr' => 'Passeport'],
            'is_active' => true, 'allow_appointment' => true, 'target_days' => 5,
            'required_documents' => ['fr' => "Acte de naissance\nPhoto d’identité\n[mineur] Autorisation parentale"],
        ]);
        $this->staff = $this->user('staff', $this->a);
        $this->consul = $this->user('consulate_admin', $this->a);
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

    private function user(string $role, ?Consulate $c, bool $active = true): User
    {
        $u = User::factory()->create(['is_active' => $active, 'consulate_id' => $c?->id]);
        $u->assignRole($role);

        return $u;
    }

    private function preRequest(string $status = 'submitted', ?CarbonImmutable $submittedAt = null): PreRequest
    {
        static $n = 0;
        $n++;
        $citizen = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->a->id, 'full_name' => 'Amina', 'email' => "amina{$n}@example.test", 'phone' => '+221 77 000 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Sénégal',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);

        return PreRequest::withoutGlobalScopes()->create([
            'consulate_id' => $this->a->id, 'citizen_id' => $citizen->id, 'service_id' => $this->service->id,
            'status' => $status, 'submitted_at' => $submittedAt ?? now(),
        ]);
    }

    private function addDocument(PreRequest $p, string $label, string $status = 'pending'): void
    {
        $p->documents()->forceCreate([
            'label' => $label, 'stored_path' => 'pre-requests/x'.uniqid(), 'original_name' => 'x.pdf',
            'mime' => 'application/pdf', 'size' => 10, 'sha256' => str_repeat('a', 64), 'status' => $status,
        ]);
    }

    // ---- Attribution ----

    public function test_staff_can_take_a_case_and_the_event_is_recorded(): void
    {
        $p = $this->preRequest();
        app(AssignPreRequest::class)($p, $this->staff, $this->staff);

        $p->refresh();
        $this->assertSame($this->staff->id, $p->assigned_to);
        $this->assertNotNull($p->assigned_at);
        $this->assertSame('assigned', $p->events()->reorder('id', 'desc')->first()->type);
    }

    public function test_staff_cannot_assign_a_case_to_someone_else(): void
    {
        $other = $this->user('staff', $this->a);
        $this->expectException(ValidationException::class);
        app(AssignPreRequest::class)($this->preRequest(), $other, $this->staff);
    }

    public function test_consul_can_assign_and_release_a_case(): void
    {
        $p = $this->preRequest();
        app(AssignPreRequest::class)($p, $this->staff, $this->consul);
        $this->assertSame($this->staff->id, $p->refresh()->assigned_to);

        app(AssignPreRequest::class)($p, null, $this->consul);
        $this->assertNull($p->refresh()->assigned_to);
        $this->assertSame(2, $p->events()->where('type', 'assigned')->count());
    }

    public function test_a_case_cannot_be_assigned_to_another_consulate_or_an_inactive_member(): void
    {
        $p = $this->preRequest();
        foreach ([$this->user('staff', $this->b), $this->user('staff', $this->a, false)] as $wrong) {
            try {
                app(AssignPreRequest::class)($p, $wrong, $this->consul);
                $this->fail('Attribution interdite acceptée.');
            } catch (ValidationException) {
                $this->assertNull($p->refresh()->assigned_to);
            }
        }
    }

    public function test_a_reviewed_case_cannot_be_assigned(): void
    {
        $this->expectException(ValidationException::class);
        app(AssignPreRequest::class)($this->preRequest('validated'), $this->staff, $this->consul);
    }

    // ---- Délais et retards ----

    public function test_due_date_and_overdue_follow_the_service_target(): void
    {
        $late = $this->preRequest('submitted', CarbonImmutable::now()->subDays(6));
        $onTime = $this->preRequest('submitted', CarbonImmutable::now()->subDays(2));
        $done = $this->preRequest('validated', CarbonImmutable::now()->subDays(10));

        $this->assertTrue($late->isOverdue());
        $this->assertFalse($onTime->isOverdue());
        $this->assertFalse($done->isOverdue());
        $this->assertSame($onTime->submitted_at->copy()->addDays(5)->toDateString(), $onTime->dueAt()->toDateString());
        $this->assertSame([$late->id], PreRequest::withoutGlobalScopes()->get()->filter->isOverdue()->pluck('id')->values()->all());
    }

    public function test_no_target_means_never_overdue(): void
    {
        $this->service->update(['target_days' => null]);
        $p = $this->preRequest('submitted', CarbonImmutable::now()->subDays(60));
        $this->assertNull($p->fresh()->dueAt());
        $this->assertFalse($p->fresh()->isOverdue());
    }

    public function test_today_widget_shows_overdue_and_my_cases(): void
    {
        $p = $this->preRequest('submitted', CarbonImmutable::now()->subDays(6));
        $p->update(['assigned_to' => $this->staff->id]);

        $this->actingAs($this->staff);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(TodayWorkWidget::class)
            ->assertSee(__('Dossiers en retard'))
            ->assertSee(__('Mes dossiers'));
    }

    // ---- Historique ----

    public function test_review_records_an_event_with_the_decision(): void
    {
        $p = $this->preRequest();
        app(ReviewPreRequest::class)($p, 'returned', 'Photo floue', $this->staff);

        $event = $p->events()->where('type', 'reviewed')->first();
        $this->assertNotNull($event);
        $this->assertStringContainsString('Photo floue', $event->note);
        $this->assertSame($this->staff->id, $event->user_id);
    }

    // ---- Pièces manquantes ----

    public function test_document_check_lists_missing_and_conditional_documents(): void
    {
        $p = $this->preRequest();
        $this->addDocument($p, '  acte DE naissance ');
        $this->addDocument($p, 'Photo d’identité', 'rejected');

        $check = $p->load('documents', 'service')->documentCheck();
        $this->assertSame(['Photo d’identité'], $check['missing']);
        $this->assertSame(['Autorisation parentale'], $check['conditional']);
    }

    public function test_a_document_named_in_another_language_counts_as_provided(): void
    {
        $this->service->setTranslation('required_documents', 'en', "Birth certificate\nID photo\n[minor] Parental authorisation")->save();
        $p = $this->preRequest();
        $this->addDocument($p, 'Acte de naissance');

        app()->setLocale('en');
        $check = $p->load('documents', 'service')->documentCheck();
        $this->assertSame(['ID photo'], $check['missing']);
        $this->assertSame(['Parental authorisation'], $check['conditional']);
    }

    public function test_citizen_api_returns_missing_documents_and_history_without_agent_names(): void
    {
        $p = $this->preRequest();
        $p->recordEvent('submitted', null, null, 'citizen');
        app(AssignPreRequest::class)($p, $this->staff, $this->consul);
        app(ReviewPreRequest::class)($p, 'returned', 'Ajoutez la photo', $this->staff);

        Sanctum::actingAs($p->citizen);
        $json = $this->getJson("/api/v1/tchad-senegal/citizen/requests/{$p->id}")->assertOk()->json('data');

        $this->assertContains('Acte de naissance', $json['missing_documents']);
        $this->assertSame(['Autorisation parentale'], $json['conditional_documents']);
        $this->assertCount(2, $json['history']); // soumission + décision ; l'attribution interne est masquée
        $this->assertSame('citizen', $json['history'][0]['by']);
        $this->assertSame('consulate', $json['history'][1]['by']);
        $this->assertStringNotContainsString($this->staff->name, json_encode($json));
    }

    public function test_citizen_submission_records_a_submitted_event(): void
    {
        $p = $this->preRequest('draft');
        $p->update(['submitted_at' => null]);
        // Dossier complet : toutes les pièces exigées par le service doivent être déposées.
        $this->addDocument($p, 'Acte de naissance');
        $this->addDocument($p, 'Photo d’identité');

        Sanctum::actingAs($p->citizen);
        $this->postJson("/api/v1/tchad-senegal/citizen/requests/{$p->id}/submit")->assertOk();

        $event = $p->events()->first();
        $this->assertSame('submitted', $event->type);
        $this->assertSame('citizen', $event->actor);
    }

    // ---- Modèles de réponses et absences ----

    public function test_templates_and_absences_are_scoped_to_the_consulate(): void
    {
        ResponseTemplate::withoutGlobalScopes()->create(['consulate_id' => $this->a->id, 'title' => 'A', 'body' => 'Texte A', 'is_active' => true]);
        ResponseTemplate::withoutGlobalScopes()->create(['consulate_id' => $this->b->id, 'title' => 'B', 'body' => 'Texte B', 'is_active' => true]);
        $ghanaStaff = $this->user('staff', $this->b);
        StaffAbsence::withoutGlobalScopes()->create(['consulate_id' => $this->b->id, 'user_id' => $ghanaStaff->id, 'starts_on' => today(), 'ends_on' => today()]);

        $this->actingAs($this->consul);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->assertSame(['A'], ResponseTemplate::query()->pluck('title')->all());
        $this->assertSame(0, StaffAbsence::query()->count());
    }

    public function test_staff_can_view_but_not_create_absences_or_templates(): void
    {
        $this->actingAs($this->staff);
        $this->assertTrue(StaffAbsenceResource::canViewAny());
        $this->assertFalse(StaffAbsenceResource::canCreate());
        $this->assertTrue(ResponseTemplateResource::canViewAny());
        $this->assertFalse(ResponseTemplateResource::canCreate());
        $this->get('/admin/staff-absences')->assertOk();
        $this->get('/admin/response-templates')->assertOk();

        $this->actingAs($this->consul);
        $this->assertTrue(StaffAbsenceResource::canCreate());
        $this->assertTrue(ResponseTemplateResource::canCreate());
    }

    public function test_pre_request_list_renders_assignment_and_overdue_columns(): void
    {
        $p = $this->preRequest('submitted', CarbonImmutable::now()->subDays(6));
        app(AssignPreRequest::class)($p, $this->staff, $this->consul);

        foreach (['', '?tableFilters[overdue][isActive]=1', '?tableFilters[mine][isActive]=1'] as $query) {
            $this->actingAs($this->staff)->get('/admin/pre-requests'.$query)
                ->assertOk()->assertSee($this->staff->name)->assertSee(__('En retard'));
        }
    }

    // ---- Calendrier ----

    public function test_calendar_is_available_to_the_team_only(): void
    {
        $monday = CarbonImmutable::today()->startOfWeek();
        StaffAbsence::withoutGlobalScopes()->create(['consulate_id' => $this->a->id, 'user_id' => $this->consul->id, 'starts_on' => $monday, 'ends_on' => $monday->addDay(), 'reason' => 'Mission']);

        $this->actingAs($this->staff)->get('/admin/calendrier-rendez-vous')->assertOk()->assertSee($this->consul->name);

        $citizenOnly = User::factory()->create(['is_active' => true, 'consulate_id' => $this->a->id]);
        $this->actingAs($citizenOnly)->get('/admin/calendrier-rendez-vous')->assertForbidden();
    }

    // ---- Rapport imprimable et comparaison ----

    public function test_printable_report_is_limited_to_the_own_consulate(): void
    {
        $month = now()->format('Y-m');
        $this->actingAs($this->consul)->get('/admin/rapport-mensuel/imprimer?month='.$month)
            ->assertOk()->assertSee('Tchad Sénégal')->assertSee(__('Visa du consul'));
        // Le paramètre consulate_id est ignoré pour le personnel : toujours son propre consulat.
        $this->actingAs($this->consul)->get('/admin/rapport-mensuel/imprimer?month='.$month.'&consulate_id='.$this->b->id)
            ->assertOk()->assertDontSee('Tchad Ghana');

        $noPerm = User::factory()->create(['is_active' => true, 'consulate_id' => $this->a->id]);
        $this->actingAs($noPerm)->get('/admin/rapport-mensuel/imprimer')->assertForbidden();

        $super = $this->user('super_admin', null);
        $this->actingAs($super)->get('/admin/rapport-mensuel/imprimer?month='.$month.'&consulate_id='.$this->b->id)
            ->assertOk()->assertSee('Tchad Ghana');
    }

    public function test_case_stats_and_global_comparison(): void
    {
        $p = $this->preRequest('submitted', CarbonImmutable::now()->subDays(2));
        app(ReviewPreRequest::class)($p, 'validated', null, $this->staff);
        $this->preRequest('submitted', CarbonImmutable::now()->subDays(8)); // en retard

        $stats = app(MonthlyReport::class)->caseStats($this->a, CarbonImmutable::now());
        $this->assertSame(1, $stats['validated']);
        $this->assertSame(1, $stats['overdue']);
        $this->assertEqualsWithDelta(2.0, $stats['avg_days'], 0.1);

        $rows = collect(app(GlobalDashboard::class)->build(CarbonImmutable::now())['rows']);
        $row = $rows->firstWhere('slug', 'tchad-senegal');
        $this->assertNotNull($row);
        $this->assertSame(1, $row['overdue']);
        $this->assertArrayHasKey('attendance', $row);
        $this->assertArrayHasKey('avg_days', $row);
    }
}

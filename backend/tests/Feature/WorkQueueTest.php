<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\FileDeTravail;
use App\Filament\Admin\Resources\PreRequestResource\Pages\ListPreRequests;
use App\Models\Citizen;
use App\Models\Consulate;
use App\Models\PreRequest;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/** Lot 2 : alertes de délai, file de travail, demande de pièces manquantes. */
class WorkQueueTest extends TestCase
{
    use RefreshDatabase;

    private Consulate $consulate;

    private Service $service;

    private Citizen $citizen;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->consulate = Consulate::create([
            'slug' => 'file-test', 'ref_prefix' => 'FIL', 'name' => ['fr' => 'Consulat test'],
            'represented_country' => ['fr' => 'Test'], 'host_country' => ['fr' => 'Test'],
            'covered_countries' => ['Test'], 'languages' => ['fr'], 'is_active' => true,
        ]);
        $this->service = Service::withoutGlobalScopes()->create([
            'consulate_id' => $this->consulate->id, 'slug' => 'passeport', 'name' => ['fr' => 'Passeport'],
            'is_active' => true, 'allow_appointment' => true, 'target_days' => 3,
            'required_documents' => ['fr' => "Acte de naissance\nPhoto d’identité"],
        ]);
        $this->citizen = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->consulate->id, 'full_name' => 'Amina Test', 'email' => 'amina@example.test',
            'phone' => '+221 77 111 22 33', 'password' => 'mot-de-passe-test', 'nationality' => 'Test',
            'residence_country' => 'Test', 'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
        $this->agent = User::factory()->create(['is_active' => true, 'consulate_id' => $this->consulate->id, 'email' => 'agent@example.test']);
        $this->agent->assignRole(User::ROLE_STAFF);
    }

    private function submitted(int $daysAgo, ?int $assignee = null, bool $withDocs = false): PreRequest
    {
        $pre = PreRequest::withoutGlobalScopes()->create([
            'consulate_id' => $this->consulate->id, 'citizen_id' => $this->citizen->id, 'service_id' => $this->service->id,
            'status' => 'submitted', 'data' => [], 'submitted_at' => now()->subDays($daysAgo), 'assigned_to' => $assignee,
        ]);
        if ($withDocs) {
            $pre->documents()->create(['label' => 'Acte de naissance', 'stored_path' => 'pre-requests/a', 'original_name' => 'a.pdf', 'mime' => 'application/pdf', 'size' => 10, 'sha256' => str_repeat('a', 64), 'status' => 'pending']);
        }

        return $pre;
    }

    public function test_overdue_request_alerts_the_assignee_once_per_day(): void
    {
        $this->submitted(10, $this->agent->id);

        Mail::shouldReceive('raw')->once()->andReturnNull();

        $this->artisan('consulat:alert-overdue')->assertExitCode(0);
        // Deuxième passage le même jour : aucune nouvelle alerte.
        $this->artisan('consulat:alert-overdue')->assertExitCode(0);
    }

    public function test_request_within_its_deadline_triggers_no_alert(): void
    {
        $this->submitted(1, $this->agent->id);

        Mail::shouldReceive('raw')->never();

        $this->artisan('consulat:alert-overdue')->assertExitCode(0);
    }

    public function test_unassigned_overdue_request_alerts_the_deciding_team(): void
    {
        $this->submitted(10, null);

        Mail::shouldReceive('raw')->once()->andReturnNull();

        $this->artisan('consulat:alert-overdue')->assertExitCode(0);
    }

    public function test_work_queue_lists_overdue_requests_first(): void
    {
        $this->submitted(1, null);
        $late = $this->submitted(10, null);

        $this->actingAs($this->agent);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(FileDeTravail::class)
            ->assertSee('#'.$late->id)
            ->assertSee('En retard');
    }

    public function test_missing_documents_can_be_requested_in_one_action(): void
    {
        $pre = $this->submitted(2, null, withDocs: false);

        $this->actingAs($this->agent);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(ListPreRequests::class)
            ->callTableAction('requestMissing', $pre);

        $pre->refresh();
        $this->assertSame('returned', $pre->status);
        $this->assertTrue($pre->events()->where('type', 'reviewed')->exists());
    }
}

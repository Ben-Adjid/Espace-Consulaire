<?php

namespace Tests\Feature;

use App\Actions\ReviewPreRequest;
use App\Enums\AppointmentStatus;
use App\Filament\Admin\Resources\AppointmentResource\Pages\ListAppointments;
use App\Mail\PreRequestDecisionMail;
use App\Models\Appointment;
use App\Models\Citizen;
use App\Models\Consulate;
use App\Models\PreRequest;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/** Traitement consulaire : examen des pré-demandes (règles, notification) et transitions de statut des rendez-vous. */
class ConsularWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Consulate $c;

    private Service $service;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->c = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
        $this->service = Service::withoutGlobalScopes()->create(['consulate_id' => $this->c->id, 'slug' => 'passeport', 'name' => ['fr' => 'Passeport'], 'is_active' => true, 'allow_appointment' => true]);
        $this->staff = User::factory()->create(['is_active' => true, 'consulate_id' => $this->c->id]);
        $this->staff->assignRole('staff');
    }

    private function preRequest(string $status = 'submitted'): PreRequest
    {
        $citizen = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->c->id, 'full_name' => 'Amina', 'email' => 'amina@example.test', 'phone' => '+221 77 000 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Sénégal', 'status' => Citizen::STATUS_ACTIVE,
        ]);

        return PreRequest::withoutGlobalScopes()->create([
            'consulate_id' => $this->c->id, 'citizen_id' => $citizen->id, 'service_id' => $this->service->id,
            'status' => $status, 'submitted_at' => now(),
        ]);
    }

    public function test_validation_records_the_reviewer_and_notifies_the_citizen(): void
    {
        $r = $this->preRequest();
        app(ReviewPreRequest::class)($r, 'validated', null, $this->staff);

        $r->refresh();
        $this->assertSame('validated', $r->status);
        $this->assertSame($this->staff->id, $r->reviewed_by);
        Mail::assertSent(PreRequestDecisionMail::class, fn ($m) => $m->hasTo('amina@example.test'));
    }

    public function test_refusal_requires_a_reason(): void
    {
        $r = $this->preRequest();

        $this->expectException(ValidationException::class);
        app(ReviewPreRequest::class)($r, 'rejected', '   ', $this->staff);
    }

    public function test_an_already_reviewed_request_cannot_be_reviewed_again(): void
    {
        $r = $this->preRequest('validated');

        try {
            app(ReviewPreRequest::class)($r, 'rejected', 'Motif', $this->staff);
            $this->fail('Une pré-demande déjà examinée ne doit pas changer de décision.');
        } catch (ValidationException) {
            $this->assertSame('validated', $r->fresh()->status);
        }
        Mail::assertNothingSent();
    }

    private function document(PreRequest $r, string $label): \App\Models\RequestDocument
    {
        return \App\Models\RequestDocument::create([
            'pre_request_id' => $r->id, 'label' => $label, 'stored_path' => 'x/'.$label.'.pdf', 'original_name' => $label.'.pdf',
            'mime' => 'application/pdf', 'size' => 1000, 'sha256' => str_repeat('a', 64), 'status' => 'pending',
        ]);
    }

    public function test_each_document_gets_its_own_decision_and_the_citizen_is_told_which_to_replace(): void
    {
        $r = $this->preRequest();
        $birth = $this->document($r, 'Acte de naissance');
        $photo = $this->document($r, 'Photo');

        app(ReviewPreRequest::class)($r, 'returned', 'Photo non conforme', $this->staff, [
            $birth->id => ['status' => 'accepted'],
            $photo->id => ['status' => 'rejected', 'note' => 'Fond non uni'],
        ]);

        $this->assertSame('accepted', $birth->fresh()->status);
        $this->assertSame('rejected', $photo->fresh()->status);
        $this->assertSame('Fond non uni', $photo->fresh()->review_note);
        $this->assertSame('returned', $r->fresh()->status);
        Mail::assertSent(PreRequestDecisionMail::class, fn ($m) => str_contains($m->render(), 'Fond non uni'));
    }

    public function test_a_refused_document_needs_a_reason(): void
    {
        $r = $this->preRequest();
        $photo = $this->document($r, 'Photo');

        $this->expectException(ValidationException::class);
        app(ReviewPreRequest::class)($r, 'returned', 'À compléter', $this->staff, [$photo->id => ['status' => 'rejected', 'note' => '']]);
    }

    public function test_cannot_validate_with_a_refused_document(): void
    {
        $r = $this->preRequest();
        $photo = $this->document($r, 'Photo');

        try {
            app(ReviewPreRequest::class)($r, 'validated', null, $this->staff, [$photo->id => ['status' => 'rejected', 'note' => 'Floue']]);
            $this->fail('Une pré-demande avec une pièce refusée ne doit pas être validée.');
        } catch (ValidationException) {
            $this->assertSame('submitted', $r->fresh()->status);
            $this->assertSame('pending', $photo->fresh()->status, 'Rien ne doit être enregistré si la décision est refusée.');
        }
    }

    public function test_validation_accepts_remaining_documents(): void
    {
        $r = $this->preRequest();
        $doc = $this->document($r, 'Passeport');

        app(ReviewPreRequest::class)($r, 'validated', null, $this->staff);

        $this->assertSame('accepted', $doc->fresh()->status);
    }

    public function test_a_document_of_another_request_cannot_be_decided(): void
    {
        $mine = $this->preRequest();
        $other = PreRequest::withoutGlobalScopes()->create([
            'consulate_id' => $this->c->id, 'citizen_id' => $mine->citizen_id, 'service_id' => $this->service->id, 'status' => 'submitted', 'submitted_at' => now(),
        ]);
        $foreign = $this->document($other, 'Autre');

        $this->expectException(ValidationException::class);
        app(ReviewPreRequest::class)($mine, 'returned', 'Motif', $this->staff, [$foreign->id => ['status' => 'rejected', 'note' => 'x']]);
    }

    public function test_citizen_must_replace_refused_documents_before_resubmitting(): void
    {
        $r = $this->preRequest();
        $photo = $this->document($r, 'Photo');
        app(ReviewPreRequest::class)($r, 'returned', 'Photo à refaire', $this->staff, [$photo->id => ['status' => 'rejected', 'note' => 'Floue']]);

        \Laravel\Sanctum\Sanctum::actingAs(Citizen::withoutGlobalScopes()->find($r->citizen_id));
        $this->postJson("/api/v1/tchad-senegal/citizen/requests/{$r->id}/submit")
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Remplacez d’abord les pièces refusées : Photo']);

        // Le citoyen retire la pièce refusée et en dépose une nouvelle : le renvoi est accepté.
        $photo->delete();
        $this->document($r, 'Photo conforme');
        $this->postJson("/api/v1/tchad-senegal/citizen/requests/{$r->id}/submit")->assertOk();
        $this->assertSame('submitted', $r->fresh()->status);
    }

    public function test_bulk_confirmation_only_touches_pending_appointments(): void
    {
        $slot = TimeSlot::withoutGlobalScopes()->create(['consulate_id' => $this->c->id, 'weekday' => 1, 'start_time' => '09:00', 'end_time' => '10:00', 'capacity' => 5, 'is_active' => true]);
        $make = fn (string $ref, AppointmentStatus $status) => Appointment::withoutGlobalScopes()->create([
            'consulate_id' => $this->c->id, 'service_id' => $this->service->id, 'time_slot_id' => $slot->id,
            'reference' => $ref, 'appointment_date' => now()->addDays(3)->toDateString(), 'start_time' => '09:00', 'end_time' => '10:00',
            'full_name' => 'Test', 'email' => $ref.'@example.test', 'phone' => '+221 77 000 00 00', 'status' => $status, 'locale' => 'fr',
        ]);
        $pending = $make('REF-P', AppointmentStatus::Pending);
        $cancelled = $make('REF-C', AppointmentStatus::Cancelled);

        $this->actingAs($this->staff);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(ListAppointments::class)->callTableBulkAction('confirm', [$pending->id, $cancelled->id]);

        $this->assertSame(AppointmentStatus::Confirmed, $pending->fresh()->status);
        $this->assertSame(AppointmentStatus::Cancelled, $cancelled->fresh()->status, 'Un rendez-vous annulé ne doit pas redevenir confirmé.');
    }
}

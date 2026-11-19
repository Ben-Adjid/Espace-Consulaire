<?php

namespace Tests\Feature;

use App\Actions\CreateAppointment;
use App\Exceptions\BookingException;
use App\Filament\Admin\Pages\FileDuJour;
use App\Models\Appointment;
use App\Models\Citizen;
use App\Models\Consulate;
use App\Models\PreRequest;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/** Rendez-vous et dossier en ligne : rattachement automatique au dossier validé, refus d'un dossier non validé ou étranger, affichage au guichet. */
class AppointmentPreRequestLinkTest extends TestCase
{
    use RefreshDatabase;

    private Consulate $c;

    private Service $service;

    private TimeSlot $slot;

    private Citizen $citizen;

    private CarbonImmutable $date;

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
        $this->service = Service::withoutGlobalScopes()->create([
            'consulate_id' => $this->c->id, 'slug' => 'passeport', 'name' => ['fr' => 'Passeport'],
            'is_active' => true, 'allow_appointment' => true,
            'required_documents' => ['fr' => "Acte de naissance\nPhoto d’identité"],
        ]);
        $this->date = CarbonImmutable::today()->addDays(3)->startOfDay();
        $this->slot = TimeSlot::withoutGlobalScopes()->create([
            'consulate_id' => $this->c->id, 'weekday' => $this->date->dayOfWeek, 'start_time' => '09:00', 'end_time' => '10:00',
            'capacity' => 5, 'is_active' => true,
        ]);
        $this->citizen = $this->citizenOf('a@example.test');
    }

    private function citizenOf(string $email): Citizen
    {
        return Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->c->id, 'full_name' => 'Amina', 'email' => $email, 'phone' => '+221 77 000 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Sénégal',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
    }

    private function preRequest(string $status, ?Citizen $owner = null): PreRequest
    {
        return PreRequest::withoutGlobalScopes()->create([
            'consulate_id' => $this->c->id, 'citizen_id' => ($owner ?? $this->citizen)->id, 'service_id' => $this->service->id,
            'status' => $status, 'submitted_at' => now()->subDay(), 'reviewed_at' => $status === 'validated' ? now() : null,
        ]);
    }

    private function book(array $extra = []): Appointment
    {
        return app(CreateAppointment::class)->execute($this->c, $this->service, $this->slot, $this->date, array_merge([
            'full_name' => 'Amina', 'email' => $this->citizen->email, 'phone' => '+221 77 000 00 00',
            'consent' => true, 'citizen_id' => $this->citizen->id,
        ], $extra));
    }

    public function test_a_booking_is_linked_automatically_to_the_validated_file_of_the_same_service(): void
    {
        $validated = $this->preRequest('validated');
        $this->preRequest('submitted'); // un autre dossier en cours ne doit pas être rattaché

        $appointment = $this->book();

        $this->assertSame($validated->id, $appointment->fresh()->pre_request_id);
    }

    public function test_without_a_validated_file_the_booking_stays_possible_but_unlinked(): void
    {
        $this->preRequest('submitted');

        $appointment = $this->book();

        $this->assertNull($appointment->fresh()->pre_request_id);
    }

    public function test_an_explicit_link_to_a_file_not_yet_validated_is_refused(): void
    {
        $pending = $this->preRequest('returned');

        $this->expectException(BookingException::class);
        $this->expectExceptionMessage('validé par le consulat');
        $this->book(['pre_request_id' => $pending->id]);
    }

    public function test_a_file_of_another_citizen_cannot_be_linked(): void
    {
        $other = $this->citizenOf('b@example.test');
        $foreign = $this->preRequest('validated', $other);

        $this->expectException(BookingException::class);
        $this->book(['pre_request_id' => $foreign->id]);
    }

    public function test_the_citizen_sees_the_linked_file_status_on_their_appointments(): void
    {
        $validated = $this->preRequest('validated');
        $appointment = $this->book();
        $this->assertSame($validated->id, $appointment->pre_request_id);

        $this->actingAs($this->citizen, 'sanctum');
        $resource = (new \App\Http\Resources\AppointmentResource($appointment->fresh(['preRequest'])))->toArray(request());

        $this->assertSame('validated', $resource['pre_request']['status']);
        $this->assertSame($validated->id, $resource['pre_request']['id']);
        $this->assertArrayNotHasKey('documents', $resource['pre_request']);
    }

    public function test_at_the_counter_a_validated_file_shows_the_originals_to_present(): void
    {
        $this->preRequest('validated');
        $appointment = $this->book();
        // L'arrivée au guichet ne se fait que le jour du rendez-vous.
        $appointment->update(['appointment_date' => today()->toDateString(), 'status' => 'confirmed']);
        $user = User::factory()->create(['is_active' => true, 'consulate_id' => $this->c->id]);
        $user->assignRole('staff');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($user)->test(FileDuJour::class)
            ->call('arrive', $appointment->id)
            ->assertNotified('Dossier en ligne validé : pièces originales à présenter');
    }

    public function test_at_the_counter_a_booking_without_validated_file_is_flagged(): void
    {
        $this->preRequest('submitted');
        $appointment = $this->book();
        // L'arrivée au guichet ne se fait que le jour du rendez-vous.
        $appointment->update(['appointment_date' => today()->toDateString(), 'status' => 'confirmed']);
        $user = User::factory()->create(['is_active' => true, 'consulate_id' => $this->c->id]);
        $user->assignRole('staff');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($user)->test(FileDuJour::class)
            ->call('arrive', $appointment->id)
            ->assertNotified('Aucun dossier en ligne pour cette démarche');
    }
}

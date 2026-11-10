<?php

namespace Tests\Feature;

use App\Actions\CreateAppointment;
use App\Enums\AppointmentStatus;
use App\Exceptions\BookingException;
use App\Models\Appointment;
use App\Models\Consulate;
use App\Models\ContactMessage;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Models\User;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class BookingAndTenancyTest extends TestCase
{
    use RefreshDatabase;

    private Consulate $chad;

    private Consulate $cameroon;

    private Service $passport;

    private Service $cameroonPassport;

    private TimeSlot $chadSlot;

    /** Prochain jour réservable tombant sur le jour de semaine du créneau. */
    private Carbon $bookableDate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->chad = Consulate::create($this->consulateData('tchad-senegal', 'TCH-SN', '#002664'));
        $this->cameroon = Consulate::create($this->consulateData('cameroun-senegal', 'CMR-SN', '#007A5E'));

        $this->passport = Service::withoutGlobalScopes()->create([
            'consulate_id' => $this->chad->id, 'slug' => 'passeport', 'name' => ['fr' => 'Passeport'],
            'is_active' => true, 'allow_appointment' => true,
        ]);
        $this->cameroonPassport = Service::withoutGlobalScopes()->create([
            'consulate_id' => $this->cameroon->id, 'slug' => 'passeport', 'name' => ['fr' => 'Passeport'],
            'is_active' => true, 'allow_appointment' => true,
        ]);

        // Créneau de 2 places, chaque lundi 09:00–10:00 (valable pour tous les services du Tchad).
        $this->chadSlot = TimeSlot::withoutGlobalScopes()->create([
            'consulate_id' => $this->chad->id, 'weekday' => 1,
            'start_time' => '09:00', 'end_time' => '10:00', 'capacity' => 2, 'is_active' => true,
        ]);

        $this->bookableDate = Carbon::today()->addDays(config('booking.min_lead_days'));
        while ($this->bookableDate->dayOfWeek !== 1) {
            $this->bookableDate->addDay();
        }
    }

    private function consulateData(string $slug, string $prefix, string $primary): array
    {
        return [
            'slug' => $slug, 'ref_prefix' => $prefix,
            'name' => ['fr' => $slug], 'represented_country' => ['fr' => 'Pays'], 'host_country' => ['fr' => 'Sénégal'],
            'languages' => ['fr'], 'primary_color' => $primary, 'secondary_color' => '#facc15', 'accent_color' => '#dc2626',
            'is_active' => true,
        ];
    }

    private function bookingData(string $email = 'a@example.test'): array
    {
        return ['full_name' => 'Test Visiteur', 'email' => $email, 'phone' => '+221 77 000 00 00', 'notes' => null];
    }

    private function book(string $email = 'a@example.test', ?TimeSlot $slot = null): Appointment
    {
        return app(CreateAppointment::class)->execute(
            $this->chad, $this->passport, $slot ?? $this->chadSlot, $this->bookableDate, $this->bookingData($email)
        );
    }

    public function test_booking_creates_pending_appointment_with_unique_reference(): void
    {
        $appointment = $this->book();

        $this->assertSame(AppointmentStatus::Pending, $appointment->status);
        $this->assertStringStartsWith('TCH-SN-', $appointment->reference);
        $this->assertSame($this->chad->id, $appointment->consulate_id);
    }

    public function test_capacity_is_enforced_and_full_slot_is_refused(): void
    {
        $this->book('one@example.test');
        $this->book('two@example.test');

        $this->expectException(BookingException::class);
        $this->book('three@example.test');
    }

    public function test_same_email_cannot_book_the_same_slot_twice(): void
    {
        $this->book('same@example.test');

        $this->expectException(BookingException::class);
        $this->book('SAME@example.test');
    }

    public function test_cancelled_appointments_free_the_place(): void
    {
        $first = $this->book('one@example.test');
        $this->book('two@example.test');
        $first->update(['status' => AppointmentStatus::Cancelled]);

        $third = $this->book('three@example.test');

        $this->assertNotNull($third->id);
    }

    public function test_availability_reflects_remaining_places(): void
    {
        $this->book('one@example.test');

        $slots = app(AvailabilityService::class)->slotsFor($this->chad, $this->passport, $this->bookableDate);

        $this->assertCount(1, $slots);
        $this->assertSame(1, $slots->first()->remaining);
    }

    public function test_slot_of_another_consulate_is_refused(): void
    {
        $foreignSlot = TimeSlot::withoutGlobalScopes()->create([
            'consulate_id' => $this->cameroon->id, 'weekday' => 1,
            'start_time' => '09:00', 'end_time' => '10:00', 'capacity' => 5, 'is_active' => true,
        ]);

        $this->expectException(BookingException::class);
        app(CreateAppointment::class)->execute(
            $this->chad, $this->passport, $foreignSlot, $this->bookableDate, $this->bookingData()
        );
    }

    public function test_public_booking_page_is_scoped_to_consulate(): void
    {
        $this->get('/tchad-senegal/appointment')->assertOk();
        $this->get('/cameroun-senegal/appointment')->assertOk();
        $this->get('/inexistant/appointment')->assertNotFound();
    }

    public function test_confirmation_page_only_shows_appointment_of_that_consulate(): void
    {
        $appointment = $this->book();

        $this->get("/tchad-senegal/appointment/confirmation/{$appointment->reference}")->assertOk();
        $this->get("/cameroun-senegal/appointment/confirmation/{$appointment->reference}")->assertNotFound();
    }

    public function test_consulate_admin_cannot_see_other_consulate_data(): void
    {
        ContactMessage::withoutGlobalScopes()->create([
            'consulate_id' => $this->cameroon->id, 'name' => 'Autre', 'email' => 'x@example.test',
            'subject' => 'Sujet', 'message' => 'Message de l’autre consulat.', 'is_read' => false,
        ]);

        $admin = User::factory()->create(['consulate_id' => $this->chad->id]);
        $admin->assignRole(User::ROLE_CONSULATE_ADMIN);

        $this->actingAs($admin);
        // Le scope ne s'applique que dans le panneau Filament : on simule le contexte admin.
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));

        $this->assertSame(0, ContactMessage::query()->count());
        $this->assertSame(0, Service::query()->where('consulate_id', $this->cameroon->id)->count());
    }

    public function test_super_admin_sees_all_consulates(): void
    {
        $super = User::factory()->create(['consulate_id' => null]);
        $super->assignRole(User::ROLE_SUPER_ADMIN);

        $this->actingAs($super);
        $this->assertSame(2, Consulate::query()->count());
    }

    public function test_contact_form_stores_message_for_its_consulate(): void
    {
        $this->post('/cameroun-senegal/contact', [
            'name' => 'Visiteur', 'email' => 'v@example.test', 'subject' => 'Question', 'message' => 'Bonjour, une question sur le passeport.',
            '_t' => Crypt::encryptString((string) (time() - 30)),
        ])->assertRedirect();

        $this->assertDatabaseHas('contact_messages', ['consulate_id' => $this->cameroon->id, 'subject' => 'Question']);
    }

    public function test_contact_honeypot_is_silently_discarded(): void
    {
        $this->post('/tchad-senegal/contact', [
            'name' => 'Robot', 'email' => 'bot@example.test', 'subject' => 'Spam', 'message' => 'Spam spam spam spam.',
            'website' => 'http://spam.example', '_t' => Crypt::encryptString((string) (time() - 30)),
        ])->assertRedirect();

        $this->assertDatabaseMissing('contact_messages', ['subject' => 'Spam']);
    }

    public function test_contact_form_sent_too_fast_is_discarded(): void
    {
        $this->post('/tchad-senegal/contact', [
            'name' => 'Robot', 'email' => 'fast@example.test', 'subject' => 'Rapide', 'message' => 'Message envoyé instantanément.',
            '_t' => Crypt::encryptString((string) time()),
        ])->assertRedirect();

        $this->assertDatabaseMissing('contact_messages', ['subject' => 'Rapide']);
    }

    public function test_appointments_are_not_listed_as_occupying_when_status_is_terminal(): void
    {
        $this->assertNotContains(AppointmentStatus::Cancelled->value, AppointmentStatus::occupying());
    }
}

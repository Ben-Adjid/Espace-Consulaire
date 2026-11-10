<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Consulate;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\News;
use App\Models\Page;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Crée les consulats de démonstration (Tchad, Cameroun) avec contenus, créneaux,
 * utilisateurs et quelques rendez-vous / messages d'exemple.
 * Idempotent : peut être relancé sans doublons (clé : slug du consulat).
 */
class ConsulateSeeder extends Seeder
{
    /** Mot de passe des comptes de démo : variable DEMO_PASSWORD du .env, sinon valeur de démo. */
    public static function demoPassword(): string
    {
        return config('demo.password');
    }

    public function run(): void
    {
        $common = require database_path('seeders/data/common.php');

        foreach (['tchad', 'cameroun'] as $file) {
            $data = require database_path("seeders/data/{$file}.php");
            $this->seedConsulate($data, $common);
        }
    }

    private function seedConsulate(array $data, array $common): void
    {
        $consulate = Consulate::updateOrCreate(
            ['slug' => $data['consulate']['slug']],
            $data['consulate'] + ['is_active' => true],
        );

        $replace = fn (array $texts) => $this->fill($texts, $data['vars']);

        // Évite les doublons si le seeder est relancé.
        foreach (['services', 'news', 'faqs', 'pages', 'timeSlots'] as $relation) {
            $consulate->{$relation}()->withoutGlobalScopes()->delete();
        }

        // Services
        $order = 0;
        $serviceIds = [];
        foreach ($common['services'] as $s) {
            $service = Service::withoutGlobalScopes()->create([
                'consulate_id' => $consulate->id,
                'slug' => $s['slug'],
                'icon' => $s['icon'],
                'name' => $replace($s['name']),
                'summary' => $replace($s['summary']),
                'description' => $replace($s['description']),
                'required_documents' => $replace($s['documents']),
                'processing_time' => $replace($s['delay']),
                'fee' => $data['fees'][$s['slug']] ?? null,
                'sort_order' => $order += 10,
                'allow_appointment' => true,
                'is_active' => true,
            ]);
            $serviceIds[$s['slug']] = $service->id;
        }

        // Actualités
        foreach ($data['news'] as $n) {
            News::withoutGlobalScopes()->create([
                'consulate_id' => $consulate->id,
                'slug' => $n['slug'],
                'title' => $n['title'],
                'excerpt' => $n['excerpt'],
                'body' => $n['body'],
                'published_at' => now()->subDays($n['days_ago']),
                'is_published' => true,
            ]);
        }

        // FAQ
        foreach ($common['faqs'] as $i => $f) {
            Faq::withoutGlobalScopes()->create([
                'consulate_id' => $consulate->id,
                'question' => $replace($f['q']),
                'answer' => $replace($f['a']),
                'sort_order' => ($i + 1) * 10,
                'is_published' => true,
            ]);
        }

        // Pages légales
        foreach ($common['pages'] as $p) {
            Page::withoutGlobalScopes()->create([
                'consulate_id' => $consulate->id,
                'slug' => $p['slug'],
                'title' => $replace($p['title']),
                'body' => $replace($p['body']),
                'is_published' => true,
            ]);
        }

        // Créneaux hebdomadaires
        $slots = [];
        foreach ($data['slots'] as [$days, $start, $end, $capacity, $serviceSlug]) {
            foreach ($days as $weekday) {
                $slots[] = TimeSlot::withoutGlobalScopes()->create([
                    'consulate_id' => $consulate->id,
                    'service_id' => $serviceSlug ? ($serviceIds[$serviceSlug] ?? null) : null,
                    'weekday' => $weekday,
                    'start_time' => $start,
                    'end_time' => $end,
                    'capacity' => $capacity,
                    'is_active' => true,
                ]);
            }
        }

        // Utilisateurs de démo
        $admin = User::updateOrCreate(['email' => $data['users']['admin']['email']], [
            'name' => $data['users']['admin']['name'],
            'password' => self::demoPassword(),
            'consulate_id' => $consulate->id,
            'is_active' => true,
        ]);
        $admin->syncRoles([User::ROLE_CONSULATE_ADMIN]);

        $staff = User::updateOrCreate(['email' => $data['users']['staff']['email']], [
            'name' => $data['users']['staff']['name'],
            'password' => self::demoPassword(),
            'consulate_id' => $consulate->id,
            'is_active' => true,
        ]);
        $staff->syncRoles([User::ROLE_STAFF]);

        $this->seedSampleActivity($consulate, $data, $slots, $serviceIds);
    }

    /** Quelques rendez-vous et messages pour que le dashboard ne soit pas vide. */
    private function seedSampleActivity(Consulate $consulate, array $data, array $slots, array $serviceIds): void
    {
        $consulate->appointments()->withoutGlobalScopes()->delete();
        $consulate->contactMessages()->withoutGlobalScopes()->delete();

        $generalSlots = collect($slots)->whereNull('service_id')->values();
        $services = array_values($serviceIds);
        $people = [
            ['Fatima Exemple', 'fatima.exemple@example.test', '+221 77 100 00 01', AppointmentStatus::Confirmed],
            ['Moussa Démo', 'moussa.demo@example.test', '+221 77 100 00 02', AppointmentStatus::Pending],
            ['Aïcha Test', 'aicha.test@example.test', '+221 77 100 00 03', AppointmentStatus::Pending],
            ['Jean Exemple', 'jean.exemple@example.test', '+221 77 100 00 04', AppointmentStatus::Confirmed],
        ];

        foreach ($people as $i => [$name, $email, $phone, $status]) {
            $slot = $generalSlots[$i % $generalSlots->count()];
            $date = $this->nextWeekday(Carbon::today()->addDays($i), $slot->weekday);

            Appointment::withoutGlobalScopes()->create([
                'consulate_id' => $consulate->id,
                'service_id' => $services[$i % count($services)],
                'time_slot_id' => $slot->id,
                'reference' => Appointment::generateReference($consulate->ref_prefix, $date->toDateString()),
                'appointment_date' => $date->toDateString(),
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'full_name' => $name,
                'email' => $email,
                'phone' => $phone,
                'status' => $status,
                'confirmed_at' => $status === AppointmentStatus::Confirmed ? now() : null,
                'locale' => 'fr',
            ]);
        }

        foreach ($data['sample_messages'] as $m) {
            ContactMessage::withoutGlobalScopes()->create($m + ['consulate_id' => $consulate->id, 'is_read' => false]);
        }
    }

    /** Premier jour >= $from tombant sur le jour de semaine voulu (0 = dimanche). */
    private function nextWeekday(Carbon $from, int $weekday): Carbon
    {
        $date = $from->copy();
        while ($date->dayOfWeek !== $weekday) {
            $date->addDay();
        }

        return $date;
    }

    /**
     * Remplace les placeholders {c} {a} {h} {n} dans chaque langue.
     *
     * @param  array<string,string>  $texts  ['fr' => …, 'en' => …, 'ar' => …]
     */
    private function fill(array $texts, array $vars): array
    {
        $out = [];
        foreach ($texts as $locale => $text) {
            $pairs = [];
            foreach ($vars as $key => $values) {
                $pairs['{'.$key.'}'] = $values[$locale] ?? $values['fr'];
            }
            $out[$locale] = strtr($text, $pairs);
        }

        return $out;
    }
}

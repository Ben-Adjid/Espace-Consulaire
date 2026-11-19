<?php

namespace Tests\Feature;

use App\Models\ClosedDate;
use App\Models\Consulate;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Services\CapacityPlan;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Plan de charge : places = créneaux × jours d'ouverture ; un jour fermé ne compte pas. */
class CapacityPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_places_count_opening_days_only(): void
    {
        $c = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ]);
        Service::withoutGlobalScopes()->create(['consulate_id' => $c->id, 'slug' => 'passeport', 'name' => ['fr' => 'Passeport'], 'is_active' => true, 'allow_appointment' => true]);
        // Un créneau le lundi, capacité 5 : novembre 2026 compte 5 lundis (2, 9, 16, 23, 30).
        TimeSlot::withoutGlobalScopes()->create(['consulate_id' => $c->id, 'weekday' => 1, 'start_time' => '09:00', 'end_time' => '10:00', 'capacity' => 5, 'is_active' => true]);

        $month = CarbonImmutable::create(2026, 11, 1);
        $before = app(CapacityPlan::class)->build($c, $month)[0];
        $this->assertSame(25, $before['places']);

        // Le lundi 2 novembre fermé retire 5 places.
        ClosedDate::create(['consulate_id' => $c->id, 'date' => '2026-11-02', 'label' => 'Férié']);
        $after = app(CapacityPlan::class)->build($c, $month)[0];
        $this->assertSame(20, $after['places']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Consulate;
use App\Models\Service;
use App\Models\ServiceFee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Historique des tarifs : tarif en vigueur à la date, tarif futur masqué, historique intangible. */
class ServiceFeeHistoryTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $c = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ]);
        $this->service = Service::withoutGlobalScopes()->create(['consulate_id' => $c->id, 'slug' => 'passeport', 'name' => ['fr' => 'Passeport'], 'is_active' => true, 'allow_appointment' => true]);
    }

    private function fee(string $from, int $amount, string $ref): ServiceFee
    {
        return ServiceFee::create(['service_id' => $this->service->id, 'amount' => $amount, 'currency' => 'XOF', 'effective_from' => $from, 'legal_reference' => $ref]);
    }

    public function test_the_fee_in_force_is_the_latest_one_whose_date_has_come(): void
    {
        $this->fee('2025-01-01', 50000, 'Arrêté A');
        $this->fee('2026-01-01', 60000, 'Arrêté B');
        $this->fee('2027-01-01', 70000, 'Arrêté C');

        Carbon::setTestNow('2026-06-15');
        $this->assertSame('Arrêté B', $this->service->feeOn()->legal_reference);
        Carbon::setTestNow();
    }

    public function test_public_api_shows_the_fee_in_force_with_its_legal_basis(): void
    {
        $this->fee('2025-01-01', 50000, 'Arrêté A');
        Carbon::setTestNow('2026-06-15');

        $this->getJson('/api/v1/tchad-senegal/services/passeport?lang=fr')
            ->assertOk()
            ->assertJsonPath('data.fee', '50 000 XOF')
            ->assertJsonPath('data.fee_legal_reference', 'Arrêté A');
        Carbon::setTestNow();
    }

    public function test_a_recorded_fee_cannot_be_modified_or_deleted(): void
    {
        $fee = $this->fee('2026-01-01', 60000, 'Arrêté B');

        $this->expectException(LogicException::class);
        $fee->update(['amount' => 1]);
    }

    public function test_a_recorded_fee_cannot_be_deleted(): void
    {
        $fee = $this->fee('2026-01-01', 60000, 'Arrêté B');

        $this->expectException(LogicException::class);
        $fee->delete();
    }
}

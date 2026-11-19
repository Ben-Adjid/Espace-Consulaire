<?php

namespace Tests\Feature;

use App\Models\Consulate;
use App\Models\ConsularReceipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

/** Journal des encaissements : numérotation, intangibilité, annulation tracée. */
class ConsularReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function consulate(string $slug, string $prefix): Consulate
    {
        return Consulate::create([
            'slug' => $slug, 'ref_prefix' => $prefix, 'name' => ['fr' => $slug],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ]);
    }

    private function receipt(Consulate $c): ConsularReceipt
    {
        return ConsularReceipt::create([
            'consulate_id' => $c->id, 'payer_name' => 'Payeur', 'amount' => 60000, 'currency' => 'XOF', 'method' => 'especes',
        ]);
    }

    public function test_receipts_are_numbered_per_consulate_and_year(): void
    {
        $sn = $this->consulate('tchad-senegal', 'TCH-SN');
        $gh = $this->consulate('tchad-ghana', 'TCH-GH');
        $year = now()->format('Y');

        $this->assertSame("TCH-SN-{$year}-000001", $this->receipt($sn)->receipt_number);
        $this->assertSame("TCH-SN-{$year}-000002", $this->receipt($sn)->receipt_number);
        $this->assertSame("TCH-GH-{$year}-000001", $this->receipt($gh)->receipt_number);
    }

    public function test_a_receipt_cannot_be_modified(): void
    {
        $r = $this->receipt($this->consulate('tchad-senegal', 'TCH-SN'));

        $this->expectException(LogicException::class);
        $r->update(['amount' => 1]);
    }

    public function test_a_receipt_cannot_be_deleted(): void
    {
        $r = $this->receipt($this->consulate('tchad-senegal', 'TCH-SN'));

        $this->expectException(LogicException::class);
        $r->delete();
    }

    public function test_a_receipt_can_be_cancelled_with_a_reason(): void
    {
        $r = $this->receipt($this->consulate('tchad-senegal', 'TCH-SN'));
        $r->update(['cancelled_at' => now(), 'cancel_reason' => 'Erreur de montant']);

        $this->assertTrue($r->fresh()->isCancelled());
        $this->assertSame('Erreur de montant', $r->fresh()->cancel_reason);
    }
}

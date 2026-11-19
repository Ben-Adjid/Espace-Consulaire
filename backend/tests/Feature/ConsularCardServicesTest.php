<?php

namespace Tests\Feature;

use App\Models\Consulate;
use App\Models\Service;
use App\Models\ServiceFee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Carte consulaire : deux variantes (papier 2 000 FCFA, numérique 5 000 FCFA), pièces confirmées, sans doublon. */
class ConsularCardServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_two_card_variants_are_created_with_their_fees_and_documents_once(): void
    {
        Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ]);

        $this->artisan('consulat:add-consular-card-services', ['consulate' => 'tchad-senegal'])->assertSuccessful();
        $this->artisan('consulat:add-consular-card-services', ['consulate' => 'tchad-senegal'])->assertSuccessful();

        $this->assertSame(2, Service::withoutGlobalScopes()->where('slug', 'like', 'carte-consulaire-%')->count());
        $paper = Service::withoutGlobalScopes()->where('slug', 'carte-consulaire-papier')->firstOrFail();
        $digital = Service::withoutGlobalScopes()->where('slug', 'carte-consulaire-numerique')->firstOrFail();
        $this->assertSame(2000.0, (float) $paper->feeOn()->amount);
        $this->assertSame(5000.0, (float) $digital->feeOn()->amount);
        $this->assertSame(2, ServiceFee::withoutGlobalScopes()->count());
        $this->assertCount(3, $paper->documentsList());
        $this->assertStringContainsString('Certificat de résidence', (string) $paper->getTranslation('required_documents', 'fr'));
    }
}

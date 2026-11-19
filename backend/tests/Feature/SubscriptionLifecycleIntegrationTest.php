<?php

namespace Tests\Feature;

use App\Models\Consulate;
use App\Models\ConsulatePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Intégration : un cycle d'abonnement complet, de bout en bout.
 * Paiement → site en ligne → échéance → grâce → suspension (site coupé) → renouvellement → site rétabli.
 */
class SubscriptionLifecycleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_subscription_cycle_takes_the_site_offline_and_back(): void
    {
        Mail::fake();

        $c = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => false,
        ]);

        // 1. Premier paiement : l'abonnement court un an, le consulat est activé.
        ConsulatePayment::create([
            'consulate_id' => $c->id, 'amount' => 150000, 'currency' => 'XOF',
            'paid_at' => now()->subYear()->toDateString(), 'period_start' => now()->subYear()->toDateString(),
            'period_end' => now()->subDays(40)->toDateString(), 'method' => 'virement',
        ]);
        $c->forceFill(['is_active' => true])->save();
        $this->getJson('/api/v1/tchad-senegal/services')->assertOk();

        // 2. Échéance dépassée de 40 jours (au-delà de la grâce de 30 jours) : la nuit suivante suspend.
        Artisan::call('consulat:check-subscriptions');
        $c->refresh();
        $this->assertFalse($c->is_active);
        $this->assertTrue($c->suspended_for_nonpayment);
        $this->getJson('/api/v1/tchad-senegal/services')->assertNotFound();

        // 3. Renouvellement : le paiement réactive le consulat automatiquement.
        ConsulatePayment::create([
            'consulate_id' => $c->id, 'amount' => 150000, 'currency' => 'XOF',
            'paid_at' => now()->toDateString(), 'period_start' => now()->toDateString(),
            'period_end' => now()->addYear()->subDay()->toDateString(), 'method' => 'virement',
        ]);
        $c->refresh();
        $this->assertTrue($c->is_active);
        $this->assertSame('active', $c->subscriptionState());
        $this->getJson('/api/v1/tchad-senegal/services')->assertOk();
    }
}

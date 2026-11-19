<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\PlatformOverviewWidget;
use App\Models\Consulate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Un consulat sans abonnement enregistré ne doit jamais être affiché comme « tous à jour ». */
class SubscriptionIndicatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_consulate_without_subscription_is_flagged_not_reported_up_to_date(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Consulate::create([
            'slug' => 'sans-abo', 'ref_prefix' => 'SAB', 'name' => ['fr' => 'Sans abonnement'],
            'represented_country' => ['fr' => 'Test'], 'host_country' => ['fr' => 'Test'],
            'covered_countries' => ['Test'], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => null,
        ]);
        $super = User::factory()->create(['is_active' => true, 'consulate_id' => null]);
        $super->assignRole(User::ROLE_SUPER_ADMIN);
        $this->actingAs($super);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(PlatformOverviewWidget::class)
            ->assertSee('1 sans abonnement enregistré')
            ->assertDontSee('Tous à jour');
    }
}

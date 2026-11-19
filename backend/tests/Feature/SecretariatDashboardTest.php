<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\TodayWorkWidget;
use App\Models\Consulate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Le secrétariat ne voit pas le compteur des dossiers qu'il ne peut pas décider. */
class SecretariatDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretariat_does_not_see_the_requests_to_decide(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $c = Consulate::create([
            'slug' => 'sec-dash', 'ref_prefix' => 'SDB', 'name' => ['fr' => 'Sec'],
            'represented_country' => ['fr' => 'Test'], 'host_country' => ['fr' => 'Test'],
            'covered_countries' => ['Test'], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
        $sec = User::factory()->create(['is_active' => true, 'consulate_id' => $c->id]);
        $sec->assignRole(User::ROLE_SECRETARIAT);
        $this->actingAs($sec);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(TodayWorkWidget::class)->assertDontSee('Pré-demandes à examiner');
    }
}

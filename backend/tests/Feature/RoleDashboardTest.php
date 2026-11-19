<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\AppointmentsByServiceChart;
use App\Filament\Admin\Widgets\MonthlyActivityWidget;
use App\Filament\Admin\Widgets\PlatformOverviewWidget;
use App\Filament\Admin\Widgets\TodayWorkWidget;
use App\Models\Consulate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Chaque rôle a son tableau de bord : plateforme (super admin), pilotage (consul), travail du jour (consul et personnel). */
class RoleDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, bool $withConsulate = true): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $consulate = $withConsulate ? Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]) : null;
        $u = User::factory()->create(['is_active' => true, 'consulate_id' => $consulate?->id]);
        $u->assignRole($role);

        return $u;
    }

    public function test_widgets_follow_the_role(): void
    {
        $this->actingAs($this->user('staff'));
        $this->assertTrue(TodayWorkWidget::canView());
        $this->assertFalse(PlatformOverviewWidget::canView());
        $this->assertFalse(AppointmentsByServiceChart::canView());
        $this->assertFalse(MonthlyActivityWidget::canView());
    }

    public function test_consul_sees_today_work_and_steering(): void
    {
        $this->actingAs($this->user('consulate_admin'));
        $this->assertTrue(TodayWorkWidget::canView());
        $this->assertTrue(MonthlyActivityWidget::canView());
        $this->assertFalse(PlatformOverviewWidget::canView());
    }

    public function test_super_admin_sees_the_platform(): void
    {
        $this->actingAs($this->user('super_admin', false));
        $this->assertTrue(PlatformOverviewWidget::canView());
        $this->assertFalse(TodayWorkWidget::canView());
    }

    public function test_shortcuts_only_show_allowed_functions(): void
    {
        $html = $this->actingAs($this->user('staff'))->get('/admin')->assertOk()->getContent();
        $this->assertStringContainsString('Agent consulaire', $html);
        $this->assertStringContainsString('File du jour', $html);
        $this->assertStringNotContainsString('Nouveau consulat', $html);
        $this->assertStringNotContainsString('Paiements d’abonnement', $html);
    }

    public function test_super_admin_shortcuts_cover_the_platform(): void
    {
        $html = $this->actingAs($this->user('super_admin', false))->get('/admin')->assertOk()->getContent();
        $this->assertStringContainsString('Super administrateur', $html);
        $this->assertStringContainsString('Nouveau consulat', $html);
    }

    public function test_staff_dashboard_renders(): void
    {
        $this->actingAs($this->user('staff'))->get('/admin')->assertOk()->assertSee('Aujourd’hui au consulat', false);
    }

    public function test_consul_dashboard_renders(): void
    {
        $this->actingAs($this->user('consulate_admin'))->get('/admin')->assertOk()->assertSee('Aujourd’hui au consulat', false);
    }

    public function test_super_admin_dashboard_renders(): void
    {
        $this->actingAs($this->user('super_admin', false))->get('/admin')->assertOk()->assertSee('Abonnements à renouveler', false);
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\AssociationResource\Pages\EditAssociation;
use App\Models\Association;
use App\Models\Consulate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** La reconnaissance officielle saisie par le consul est bien enregistrée. */
class AssociationRecognitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_recognition_is_saved_by_the_consul(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $consulate = Consulate::create([
            'slug' => 'reco-test', 'ref_prefix' => 'RCO', 'name' => ['fr' => 'Reco'],
            'represented_country' => ['fr' => 'Test'], 'host_country' => ['fr' => 'Test'],
            'covered_countries' => ['Test'], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
        $association = Association::withoutGlobalScopes()->create(['consulate_id' => $consulate->id, 'name' => 'RCO', 'is_public' => true]);
        $consul = User::factory()->create(['is_active' => true, 'consulate_id' => $consulate->id]);
        $consul->assignRole(User::ROLE_CONSULATE_ADMIN);
        $this->actingAs($consul);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(EditAssociation::class, ['record' => $association->getRouteKey()])
            ->fillForm(['is_recognized' => true, 'recognized_at' => '2026-11-19'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($association->refresh()->is_recognized);
    }
}

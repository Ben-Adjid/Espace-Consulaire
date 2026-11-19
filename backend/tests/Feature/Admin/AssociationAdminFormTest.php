<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\AssociationResource\Pages\EditAssociation;
use App\Filament\Admin\Resources\AssociationResource\RelationManagers\ElectionsRelationManager;
use App\Models\Association;
use App\Models\AssociationElection;
use App\Models\Consulate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Tables\Actions\CreateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Formulaires admin de l'association : création d'une élection en ligne, reconnaissance enregistrée. */
class AssociationAdminFormTest extends TestCase
{
    use RefreshDatabase;

    private Association $association;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $consulate = Consulate::create([
            'slug' => 'form-test', 'ref_prefix' => 'FRM', 'name' => ['fr' => 'Form'],
            'represented_country' => ['fr' => 'Test'], 'host_country' => ['fr' => 'Test'],
            'covered_countries' => ['Test'], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
        $this->association = Association::withoutGlobalScopes()->create(['consulate_id' => $consulate->id, 'name' => 'AFT', 'is_public' => true]);

        $admin = User::factory()->create(['is_active' => true, 'consulate_id' => $consulate->id]);
        $admin->assignRole(User::ROLE_CONSULATE_ADMIN);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_online_election_is_created_without_an_offline_board(): void
    {
        Livewire::test(ElectionsRelationManager::class, [
            'ownerRecord' => $this->association,
            'pageClass' => EditAssociation::class,
        ])
            ->callTableAction(CreateAction::class, data: [
                'year' => 2026,
                'held_on' => '2026-11-21',
                'voting_opens_at' => '2026-11-20 09:00:00',
                'voting_closes_at' => '2026-11-21 18:00:00',
                'second_round_hours' => 72,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertTrue(AssociationElection::where('association_id', $this->association->id)->exists());
    }


    public function test_a_second_election_for_the_same_year_is_refused_with_a_visible_reason(): void
    {
        $data = [
            'year' => 2026,
            'held_on' => '2026-11-21',
            'voting_opens_at' => '2026-11-20 09:00:00',
            'voting_closes_at' => '2026-11-21 18:00:00',
            'second_round_hours' => 72,
        ];
        Livewire::test(ElectionsRelationManager::class, [
            'ownerRecord' => $this->association,
            'pageClass' => EditAssociation::class,
        ])
            ->callTableAction(CreateAction::class, data: $data)
            ->assertHasNoTableActionErrors();

        Livewire::test(ElectionsRelationManager::class, [
            'ownerRecord' => $this->association,
            'pageClass' => EditAssociation::class,
        ])
            ->callTableAction(CreateAction::class, data: $data)
            ->assertNotified();

        $this->assertSame(1, AssociationElection::where('association_id', $this->association->id)->count());
    }
}

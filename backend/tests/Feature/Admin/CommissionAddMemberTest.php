<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\AssociationResource\Pages\EditAssociation;
use App\Filament\Admin\Resources\AssociationResource\RelationManagers\CommissionRelationManager;
use App\Models\Association;
use App\Models\AssociationCommissionMember;
use App\Models\Citizen;
use App\Models\Consulate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Tables\Actions\Action;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Ajout d'un membre de commission depuis la fiche association : le formulaire aboutit. */
class CommissionAddMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_consul_can_add_a_commission_member_from_the_form(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $c = Consulate::create([
            'slug' => 'comm-test', 'ref_prefix' => 'COM', 'name' => ['fr' => 'Comm'],
            'represented_country' => ['fr' => 'Test'], 'host_country' => ['fr' => 'Test'],
            'covered_countries' => ['Test'], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
        $assoc = Association::withoutGlobalScopes()->create(['consulate_id' => $c->id, 'name' => 'COM', 'is_public' => true]);
        $electeur = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $c->id, 'full_name' => 'Électeur', 'email' => 'el@example.test', 'phone' => '+000 00 00 00 09',
            'password' => 'mot-de-passe-test', 'nationality' => 'Test', 'residence_country' => 'Test',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
        $consul = User::factory()->create(['is_active' => true, 'consulate_id' => $c->id]);
        $consul->assignRole(User::ROLE_CONSULATE_ADMIN);
        $this->actingAs($consul);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(CommissionRelationManager::class, ['ownerRecord' => $assoc, 'pageClass' => EditAssociation::class])
            ->mountTableAction('add')
            ->setTableActionData(['citizen_id' => $electeur->id])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $this->assertSame(1, AssociationCommissionMember::where('association_id', $assoc->id)->count());
    }
}

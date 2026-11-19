<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\ConsularReceiptResource\Pages\ListConsularReceipts;
use App\Models\Consulate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Un encaissement doit désigner la démarche payée. */
class ReceiptServiceRequiredTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_receipt_without_service_is_refused(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $c = Consulate::create([
            'slug' => 'rcpt-test', 'ref_prefix' => 'RCT', 'name' => ['fr' => 'Reçu'],
            'represented_country' => ['fr' => 'Test'], 'host_country' => ['fr' => 'Test'],
            'covered_countries' => ['Test'], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
        $staff = User::factory()->create(['is_active' => true, 'consulate_id' => $c->id]);
        $staff->assignRole(User::ROLE_STAFF);
        $this->actingAs($staff);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ListConsularReceipts::class)
            ->callAction('create', data: ['payer_name' => 'Test', 'amount' => 2000, 'method' => 'cash', 'currency' => 'XOF'])
            ->assertHasActionErrors(['service_id' => 'required']);
    }
}

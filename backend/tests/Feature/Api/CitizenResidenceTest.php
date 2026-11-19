<?php

namespace Tests\Feature\Api;

use App\Models\Citizen;
use App\Models\Consulate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Règle de rattachement : trois consulats du Tchad (Sénégal, Cameroun, Ghana).
 * Chaque consulat n'accepte que les citoyens résidant dans son pays ; un numéro d'un autre pays est signalé.
 */
class CitizenResidenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->consulate('tchad-senegal', 'Sénégal');
        $this->consulate('tchad-cameroun', 'Cameroun');
        $this->consulate('tchad-ghana', 'Ghana');
    }

    private function consulate(string $slug, string $host): Consulate
    {
        return Consulate::create([
            'slug' => $slug, 'ref_prefix' => strtoupper(substr($slug, -2)), 'name' => ['fr' => $slug],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => $host],
            'covered_countries' => [$host], 'languages' => ['fr'], 'is_active' => true,
        ]);
    }

    private function register(string $slug, string $residence, string $phone = '+235 66 00 00 00'): \Illuminate\Testing\TestResponse
    {
        return $this->postJson("/api/v1/{$slug}/citizen/register", [
            'full_name' => 'Citoyen Test', 'email' => uniqid('c', true).'@example.test', 'phone' => $phone,
            'password' => 'mot-de-passe-test', 'password_confirmation' => 'mot-de-passe-test',
            'residence_country' => $residence, 'nationality' => true, 'consent' => true,
        ]);
    }

    public function test_a_chadian_in_ghana_registers_at_the_ghana_consulate(): void
    {
        $this->register('tchad-ghana', 'Ghana')->assertCreated();
    }

    public function test_a_chadian_in_senegal_cannot_register_at_the_ghana_consulate(): void
    {
        $this->register('tchad-ghana', 'Sénégal')
            ->assertUnprocessable()
            ->assertJsonPath('suggested_consulate.slug', 'tchad-senegal');
    }

    public function test_a_chadian_in_cameroon_is_sent_to_the_cameroon_consulate(): void
    {
        $this->register('tchad-senegal', 'Cameroun')
            ->assertUnprocessable()
            ->assertJsonPath('suggested_consulate.slug', 'tchad-cameroun');
    }

    public function test_a_senegalese_phone_for_a_cameroon_residence_is_flagged_but_not_refused(): void
    {
        $this->register('tchad-cameroun', 'Cameroun', '+221 77 000 00 00')->assertCreated();

        $this->assertTrue(Citizen::withoutGlobalScopes()->latest('id')->first()->phone_mismatch);
    }

    public function test_a_chadian_number_is_not_flagged(): void
    {
        $this->register('tchad-cameroun', 'Cameroun', '+235 66 00 00 00')->assertCreated();

        $this->assertFalse(Citizen::withoutGlobalScopes()->latest('id')->first()->phone_mismatch);
    }

    public function test_the_three_consulates_are_the_only_ones_with_their_own_country(): void
    {
        $this->assertSame(['Sénégal', 'Cameroun', 'Ghana'], Consulate::withoutGlobalScopes()->orderBy('id')->get()
            ->map(fn (Consulate $c) => $c->covered_countries[0])->all());
        $this->assertTrue(Consulate::withoutGlobalScopes()->get()->every(fn (Consulate $c) => $c->representedKey() === 'tchad'));
    }
}

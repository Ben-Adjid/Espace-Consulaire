<?php

namespace Tests\Unit;

use App\Support\PhoneCountry;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Détection du pays d'un numéro et signalement d'incohérence avec la résidence. */
class PhoneCountryTest extends TestCase
{
    public static function numbers(): array
    {
        return [
            'sénégalais' => ['+221 77 000 00 00', 'senegal'],
            'camerounais' => ['+237 6 90 00 00 00', 'cameroun'],
            'ghanéen' => ['+233 24 000 0000', 'ghana'],
            'tchadien' => ['+235 66 00 00 00', 'tchad'],
            'format 00' => ['00221770000000', 'senegal'],
            'avec tirets' => ['+233-24-000-0000', 'ghana'],
            'sans indicatif' => ['770000000', null],
            'indicatif inconnu' => ['+999 12345', null],
        ];
    }

    #[DataProvider('numbers')]
    public function test_country_is_detected_from_the_international_prefix(string $phone, ?string $expected): void
    {
        $this->assertSame($expected, PhoneCountry::countryOf($phone));
    }

    public function test_number_of_another_country_is_flagged_against_the_residence(): void
    {
        // Un numéro sénégalais déclaré pour une résidence au Cameroun : signalé.
        $this->assertTrue(PhoneCountry::mismatchesResidence('+221 77 000 00 00', 'Cameroun'));
        // Même pays : pas de signalement.
        $this->assertFalse(PhoneCountry::mismatchesResidence('+221 77 000 00 00', 'Sénégal'));
    }

    public function test_chadian_number_is_never_flagged(): void
    {
        // Un Tchadien résidant au Ghana garde souvent son numéro d'origine : situation normale.
        $this->assertFalse(PhoneCountry::mismatchesResidence('+235 66 00 00 00', 'Ghana'));
    }

    public function test_unknown_prefix_is_not_flagged(): void
    {
        $this->assertFalse(PhoneCountry::mismatchesResidence('+999 12345', 'Ghana'));
    }
}

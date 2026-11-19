<?php

namespace App\Support;

use App\Models\Consulate;

/**
 * Pays associé à un numéro de téléphone (indicatif international).
 * Sert uniquement à SIGNALER une incohérence avec le pays de résidence déclaré : jamais à refuser seul,
 * car un Tchadien vivant à l'étranger peut garder son numéro d'origine.
 */
class PhoneCountry
{
    /** Indicatif → pays (clé comparable, voir Consulate::countryKey). */
    private const PREFIXES = [
        '235' => 'tchad',
        '221' => 'senegal',
        '237' => 'cameroun',
        '233' => 'ghana',
        '234' => 'nigeria',
        '225' => 'cote d ivoire',
        '228' => 'togo',
        '229' => 'benin',
        '227' => 'niger',
        '226' => 'burkina faso',
        '223' => 'mali',
        '224' => 'guinee',
        '242' => 'congo',
        '243' => 'rd congo',
        '241' => 'gabon',
        '212' => 'maroc',
        '213' => 'algerie',
        '216' => 'tunisie',
        '218' => 'libye',
        '20' => 'egypte',
        '33' => 'france',
        '32' => 'belgique',
        '49' => 'allemagne',
        '44' => 'royaume uni',
        '1' => 'etats unis',
        '966' => 'arabie saoudite',
        '971' => 'emirats arabes unis',
        '974' => 'qatar',
        '250' => 'rwanda',
        '256' => 'ouganda',
        '254' => 'kenya',
        '27' => 'afrique du sud',
    ];

    /** Pays détecté d'après l'indicatif, ou null si inconnu (préfixe le plus long d'abord). */
    public static function countryOf(string $phone): ?string
    {
        $digits = preg_replace('/[^0-9+]/', '', $phone) ?? '';
        if (str_starts_with($digits, '00')) {
            $digits = '+'.substr($digits, 2);
        }
        if (! str_starts_with($digits, '+')) {
            return null;
        }
        $digits = substr($digits, 1);

        for ($len = 3; $len >= 1; $len--) {
            $prefix = substr($digits, 0, $len);
            if (isset(self::PREFIXES[$prefix])) {
                return self::PREFIXES[$prefix];
            }
        }

        return null;
    }

    /**
     * Vrai si le numéro indique un autre pays que le pays de résidence déclaré.
     * Un numéro tchadien (+235) n'est jamais signalé : c'est le pays d'origine, situation normale à l'étranger.
     */
    public static function mismatchesResidence(string $phone, string $residence): bool
    {
        $country = self::countryOf($phone);
        if ($country === null || $country === 'tchad') {
            return false;
        }

        return $country !== Consulate::countryKey($residence);
    }
}

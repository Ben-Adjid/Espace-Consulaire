<?php

namespace App\Support;

/**
 * Garde-fou des commandes destructives (purge, suppression de données de démonstration).
 * En production, elles sont refusées sauf si ALLOW_DESTRUCTIVE_COMMANDS=true est défini explicitement.
 * Hors production (local, testing, staging), elles restent disponibles.
 */
class EnvironmentGuard
{
    public static function allowsDestructive(): bool
    {
        if (! app()->environment('production')) {
            return true;
        }

        return filter_var(env('ALLOW_DESTRUCTIVE_COMMANDS', false), FILTER_VALIDATE_BOOLEAN);
    }

    public static function refusalMessage(): string
    {
        return 'Refusé : commande destructive en production. Définissez ALLOW_DESTRUCTIVE_COMMANDS=true uniquement après une sauvegarde vérifiée.';
    }
}

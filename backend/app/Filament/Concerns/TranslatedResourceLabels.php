<?php

namespace App\Filament\Concerns;

/**
 * Traduit à l'affichage les libellés d'une ressource (menu, groupe, nom au singulier et au pluriel).
 * Les textes sources sont en français ; les traductions sont dans lang/en.json et lang/ar.json.
 */
trait TranslatedResourceLabels
{
    public static function getNavigationLabel(): string
    {
        return __(parent::getNavigationLabel());
    }

    public static function getNavigationGroup(): ?string
    {
        $group = parent::getNavigationGroup();

        return $group === null ? null : __($group);
    }

    public static function getModelLabel(): string
    {
        return __(parent::getModelLabel());
    }

    public static function getPluralModelLabel(): string
    {
        return __(parent::getPluralModelLabel());
    }
}

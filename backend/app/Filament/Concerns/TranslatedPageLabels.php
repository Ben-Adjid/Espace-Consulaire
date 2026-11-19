<?php

namespace App\Filament\Concerns;

use Illuminate\Contracts\Support\Htmlable;

/** Traduit à l'affichage le menu, le groupe et le titre d'une page d'administration. */
trait TranslatedPageLabels
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

    public function getTitle(): string|Htmlable
    {
        $title = parent::getTitle();

        return is_string($title) ? __($title) : $title;
    }
}

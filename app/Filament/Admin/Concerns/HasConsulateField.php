<?php

namespace App\Filament\Admin\Concerns;

use App\Models\Consulate;
use Filament\Forms\Components\Select;

/** Champ « Consulat » : visible uniquement pour le super_admin (les autres sont verrouillés sur leur consulat). */
trait HasConsulateField
{
    public static function consulateField(): Select
    {
        return Select::make('consulate_id')
            ->label('Consulat')
            ->options(fn () => Consulate::query()->orderBy('slug')->get()
                ->mapWithKeys(fn (Consulate $c) => [$c->id => $c->getTranslation('name', 'fr')])->all())
            ->required()
            ->searchable()
            ->visible(fn () => auth()->user()?->isSuperAdmin());
    }
}

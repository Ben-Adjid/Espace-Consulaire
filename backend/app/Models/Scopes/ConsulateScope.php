<?php

namespace App\Models\Scopes;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Isolation multi-consulats dans le panneau d'administration.
 *
 * - super_admin : aucune restriction ;
 * - consulate_admin / staff : uniquement les données de leur consulat.
 *
 * Le scope n'est actif que dans le panneau Filament : le site public
 * filtre explicitement par consulat (voir ConsulateContext).
 */
class ConsulateScope implements Scope
{
    public function __construct(private string $column = 'consulate_id')
    {
    }

    public function apply(Builder $builder, Model $model): void
    {
        if (Filament::getCurrentPanel() === null) {
            return;
        }

        $user = auth()->user();

        if (! $user || $user->isSuperAdmin()) {
            return;
        }

        // Utilisateur rattaché à aucun consulat : aucune donnée visible.
        if (! $user->consulate_id) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->qualifyColumn($this->column), $user->consulate_id);
    }
}

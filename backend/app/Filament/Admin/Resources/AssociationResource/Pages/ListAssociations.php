<?php

namespace App\Filament\Admin\Resources\AssociationResource\Pages;

use App\Filament\Admin\Resources\AssociationResource;
use App\Models\Association;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssociations extends ListRecords
{
    protected static string $resource = AssociationResource::class;

    /**
     * Bouton « Nouvelle association ». Un consulat n'a qu'une association :
     * le bouton disparaît dès qu'elle existe pour le consulat de l'utilisateur (le super admin, lui, choisit le consulat).
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('Nouvelle association'))
                ->visible(function () {
                    $user = auth()->user();

                    return $user?->isSuperAdmin()
                        || ! Association::withoutGlobalScopes()->where('consulate_id', $user?->consulate_id)->exists();
                }),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\StaffAbsenceResource\Pages;

use App\Filament\Admin\Resources\StaffAbsenceResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageStaffAbsences extends ManageRecords
{
    protected static string $resource = StaffAbsenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('Nouvelle absence'))
                // Le membre choisi doit appartenir au consulat de l'absence (contrôle serveur).
                ->mutateFormDataUsing(function (array $data) {
                    $member = User::find($data['user_id'] ?? null);
                    $consulateId = auth()->user()?->isSuperAdmin() ? ($data['consulate_id'] ?? null) : auth()->user()?->consulate_id;
                    abort_unless($member && (int) $member->consulate_id === (int) $consulateId, 422, __('Ce membre ne fait pas partie de l’équipe du consulat.'));
                    $data['consulate_id'] = $consulateId;

                    return $data;
                }),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $user */
        $user = $this->record;
        $data['role'] = $user->roles->first()?->name;

        return $data;
    }

    protected function afterSave(): void
    {
        $role = $this->data['role'] ?? null;

        if (! array_key_exists($role, UserResource::assignableRoles())) {
            throw ValidationException::withMessages(['data.role' => 'Rôle non autorisé.']);
        }

        /** @var User $user */
        $user = $this->record;
        $user->syncRoles([$role]);
    }
}

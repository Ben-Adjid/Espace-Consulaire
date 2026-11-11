<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /** Un consulate_admin crée uniquement des comptes de SON consulat. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $actor = auth()->user();

        if (! $actor->isSuperAdmin()) {
            $data['consulate_id'] = $actor->consulate_id;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $role = $this->data['role'] ?? null;

        // Contrôle côté serveur : jamais de rôle hors de la liste autorisée.
        if (! array_key_exists($role, UserResource::assignableRoles())) {
            throw ValidationException::withMessages(['data.role' => 'Rôle non autorisé.']);
        }

        /** @var User $user */
        $user = $this->record;
        $user->syncRoles([$role]);
    }
}

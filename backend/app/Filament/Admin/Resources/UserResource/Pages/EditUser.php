<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use App\Models\ActivityLog;
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
        $previousRole = $user->roles()->value('name');
        $user->syncRoles([$role]);

        if ($previousRole !== $role) {
            ActivityLog::query()->create([
                'consulate_id' => $user->consulate_id,
                'actor_id' => auth()->id(),
                'action' => 'role_updated',
                'subject_type' => User::class,
                'subject_id' => (string) $user->getKey(),
                'changed_fields' => ['role'],
            ]);
        }
    }
}

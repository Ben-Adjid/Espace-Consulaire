<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Actions\SendAdminInvitation;
use App\Filament\Admin\Resources\UserResource;
use App\Models\ActivityLog;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /** Aucun mot de passe saisi : la personne recevra un lien d'activation. */
    private bool $sendInvitation = false;

    /** Un consulate_admin crée uniquement des comptes de SON consulat. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $actor = auth()->user();

        if (! $actor->isSuperAdmin()) {
            $data['consulate_id'] = $actor->consulate_id;
        }

        // Mot de passe aléatoire inutilisable : seul le lien d'activation permet d'en choisir un.
        if (empty($data['password'])) {
            $data['password'] = Hash::make(Str::random(48));
            $this->sendInvitation = true;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->sendInvitation) {
            app(SendAdminInvitation::class)->execute($this->record);
        }

        $role = $this->data['role'] ?? null;

        // Contrôle côté serveur : jamais de rôle hors de la liste autorisée.
        if (! array_key_exists($role, UserResource::assignableRoles())) {
            throw ValidationException::withMessages(['data.role' => __('Rôle non autorisé.')]);
        }

        /** @var User $user */
        $user = $this->record;
        $user->syncRoles([$role]);

        ActivityLog::query()->create([
            'consulate_id' => $user->consulate_id,
            'actor_id' => auth()->id(),
            'action' => 'role_assigned',
            'subject_type' => User::class,
            'subject_id' => (string) $user->getKey(),
            'changed_fields' => ['role'],
        ]);
    }
}

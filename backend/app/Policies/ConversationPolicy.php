<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Messagerie citoyens : lecture et réponse avec la permission manage_messages, dans son consulat.
 * Pas de création depuis la liste (le consulat écrit depuis une pré-demande) ni de suppression :
 * les échanges sont conservés comme trace.
 */
class ConversationPolicy extends ConsulateOwnedPolicy
{
    protected function managePermission(): string
    {
        return 'manage_messages';
    }

    protected function viewPermission(): string
    {
        return 'manage_messages';
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Model $model): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

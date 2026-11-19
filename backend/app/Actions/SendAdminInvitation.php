<?php

namespace App\Actions;

use App\Mail\AdminInvitationMail;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

/**
 * Envoie à un membre du personnel (consul, agent) un lien d'activation pour choisir son mot de passe.
 * Le lien est signé et utilise le jeton de réinitialisation (valable 60 minutes).
 */
class SendAdminInvitation
{
    public function execute(User $user): void
    {
        $token = Password::broker('users')->createToken($user);
        $url = Filament::getPanel('admin')->getResetPasswordUrl($token, $user);

        Mail::to($user->email)->send(new AdminInvitationMail($user, $url));
    }
}

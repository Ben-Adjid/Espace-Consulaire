<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Invitation du personnel consulaire : lien pour activer le compte et choisir son mot de passe. */
class AdminInvitationMail extends Mailable
{
    use SerializesModels;

    public function __construct(public User $user, public string $url)
    {
        // Langue du destinataire : sujet et contenu rendus dans sa langue (fr, en, ar).
        $this->locale(in_array($user->locale, ['fr', 'en', 'ar'], true) ? $user->locale : 'fr');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Activez votre compte d’administration'));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-invitation',
            with: [
                'user' => $this->user,
                'url' => $this->url,
                'consulateName' => $this->user->consulate?->getTranslation('name', app()->getLocale()) ?? 'Plateforme des consulats',
            ],
        );
    }
}

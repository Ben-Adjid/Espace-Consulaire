<?php

namespace App\Mail;

use App\Models\Citizen;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Prévient l'ancienne adresse qu'un changement d'email a été demandé (sécurité du compte). */
class EmailChangeNoticeMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Citizen $citizen, public string $newEmail)
    {
        // Langue du destinataire : sujet et contenu rendus dans sa langue (fr, en, ar).
        $this->locale(in_array($citizen->locale, ['fr', 'en', 'ar'], true) ? $citizen->locale : 'fr');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Demande de changement de votre adresse email'));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-change-notice',
            with: [
                'citizen' => $this->citizen,
                'newEmail' => $this->newEmail,
                'dir' => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',
            ],
        );
    }
}

<?php

namespace App\Mail;

use App\Models\Citizen;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Email contenant un lien d'action : vérification de l'email ou réinitialisation du mot de passe. */
class CitizenLinkMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Citizen $citizen, public string $purpose, public string $url)
    {
        // Langue du destinataire : sujet et contenu rendus dans sa langue (fr, en, ar).
        $this->locale(in_array($citizen->locale, ['fr', 'en', 'ar'], true) ? $citizen->locale : 'fr');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->purpose === 'verify'
            ? __('Confirmez votre adresse email')
            : __('Réinitialisation de votre mot de passe'));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.citizen-link',
            with: [
                'citizen' => $this->citizen,
                'purpose' => $this->purpose,
                'url' => $this->url,
                'consulateName' => $this->citizen->consulate?->getTranslation('name', app()->getLocale()),
                'dir' => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',
            ],
        );
    }
}

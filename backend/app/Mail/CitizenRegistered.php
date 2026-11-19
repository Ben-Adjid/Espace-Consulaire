<?php

namespace App\Mail;

use App\Models\Citizen;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Accusé de réception d'une demande de compte citoyen (en attente de validation). */
class CitizenRegistered extends Mailable
{
    use SerializesModels;

    public function __construct(public Citizen $citizen)
    {
        // Langue du destinataire : sujet et contenu rendus dans sa langue (fr, en, ar).
        $this->locale(in_array($citizen->locale, ['fr', 'en', 'ar'], true) ? $citizen->locale : 'fr');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Votre demande de compte a bien été reçue'));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.citizen-account',
            with: [
                'citizen' => $this->citizen,
                'consulateName' => $this->citizen->consulate?->getTranslation('name', app()->getLocale()),
                'kind' => 'registered',
                'dir' => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',
            ],
        );
    }
}

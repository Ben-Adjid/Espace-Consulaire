<?php

namespace App\Mail;

use App\Models\Citizen;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Décision du consulat sur un compte citoyen : activé ou refusé (avec motif). */
class CitizenDecision extends Mailable
{
    use SerializesModels;

    public function __construct(public Citizen $citizen, public string $kind)
    {
        // Langue du destinataire : sujet et contenu rendus dans sa langue (fr, en, ar).
        $this->locale(in_array($citizen->locale, ['fr', 'en', 'ar'], true) ? $citizen->locale : 'fr');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->kind === 'active'
            ? __('Votre compte est activé')
            : __('Votre demande de compte n’a pas été acceptée'));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.citizen-account',
            with: [
                'citizen' => $this->citizen,
                'consulateName' => $this->citizen->consulate?->getTranslation('name', app()->getLocale()),
                'kind' => $this->kind,
                'dir' => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',
            ],
        );
    }
}

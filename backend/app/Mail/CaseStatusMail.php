<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Avis au citoyen : son dossier a changé d'étape (ex. « prêt à retirer »). */
class CaseStatusMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Appointment $appointment)
    {
        // Langue du destinataire : sujet et contenu rendus dans sa langue (fr, en, ar).
        $this->locale(in_array($appointment->locale, ['fr', 'en', 'ar'], true) ? $appointment->locale : 'fr');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Votre dossier').' '.$this->appointment->reference.' — '.$this->appointment->case_status?->label());
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.case-status',
            with: [
                'appointment' => $this->appointment,
                'consulateName' => $this->appointment->consulate?->getTranslation('name', app()->getLocale()),
                'dir' => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',
            ],
        );
    }
}

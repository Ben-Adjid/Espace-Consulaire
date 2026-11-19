<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Models\ClosedDate;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Alerte : le consulat est fermé le jour de votre rendez-vous. */
class ClosureNotice extends Mailable
{
    use SerializesModels;

    public function __construct(public Appointment $appointment, public ClosedDate $closure)
    {
        // Langue du destinataire : sujet et contenu rendus dans sa langue (fr, en, ar).
        $this->locale(in_array($appointment->locale, ['fr', 'en', 'ar'], true) ? $appointment->locale : 'fr');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Fermeture du consulat le jour de votre rendez-vous').' — '.$this->appointment->reference);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.closure-notice', with: [
            'appointment' => $this->appointment,
            'closure' => $this->closure,
            'consulateName' => $this->appointment->consulate?->getTranslation('name', app()->getLocale()),
            'dir' => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',
        ]);
    }
}

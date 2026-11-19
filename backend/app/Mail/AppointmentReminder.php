<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Rappel envoyé la veille du rendez-vous. */
class AppointmentReminder extends Mailable
{
    use SerializesModels;

    public function __construct(public Appointment $appointment)
    {
        // Langue du destinataire : sujet et contenu rendus dans sa langue (fr, en, ar).
        $this->locale(in_array($appointment->locale, ['fr', 'en', 'ar'], true) ? $appointment->locale : 'fr');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Rappel : votre rendez-vous demain').' — '.$this->appointment->reference);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reminder',
            with: [
                'appointment' => $this->appointment,
                'consulate' => $this->appointment->consulate,
                'service' => $this->appointment->service,
                'dir' => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',
            ],
        );
    }
}

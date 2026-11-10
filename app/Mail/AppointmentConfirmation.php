<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Email de confirmation de prise de rendez-vous (avec la référence unique). */
class AppointmentConfirmation extends Mailable
{
    use SerializesModels;

    public function __construct(public Appointment $appointment)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Votre demande de rendez-vous').' — '.$this->appointment->reference
        );
    }

    public function content(): Content
    {
        $consulate = $this->appointment->consulate;

        return new Content(
            view: 'emails.appointment',
            with: [
                'appointment' => $this->appointment,
                'consulate' => $consulate,
                'service' => $this->appointment->service,
                'dir' => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',
            ],
        );
    }
}

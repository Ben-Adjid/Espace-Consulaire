<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Email de confirmation avec la référence et le lien personnel de gestion (jeton). */
class AppointmentConfirmation extends Mailable
{
    use SerializesModels;

    public function __construct(public Appointment $appointment)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Votre demande de rendez-vous').' — '.$this->appointment->reference);
    }

    public function content(): Content
    {
        $consulate = $this->appointment->consulate;
        $token = $this->appointment->plainAccessToken;

        // Le lien pointe vers le frontend, avec le jeton en clair (non stocké côté serveur).
        $manageUrl = $token
            ? rtrim(config('app.frontend_url'), '/').'/'.$consulate->slug.'/'.app()->getLocale()
                .'/appointment/confirmation/'.$this->appointment->reference.'#token='.$token
            : null;

        return new Content(
            view: 'emails.appointment',
            with: [
                'appointment' => $this->appointment,
                'consulate' => $consulate,
                'service' => $this->appointment->service,
                'manageUrl' => $manageUrl,
                'dir' => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',
            ],
        );
    }
}

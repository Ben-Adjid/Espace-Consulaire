<?php

namespace App\Mail;

use App\Models\Citizen;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Rappel au citoyen : un document arrive à expiration (pièce déposée) ou une validité prend fin (carte, visa, passeport). */
class ValidityReminderMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Citizen $citizen, public string $what, public string $date, public string $url)
    {
        $this->locale(in_array($citizen->locale, ['fr', 'en', 'ar'], true) ? $citizen->locale : 'fr');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Rappel : échéance proche').' — '.$this->what);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.validity-reminder', with: [
            'dir' => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',
        ]);
    }
}

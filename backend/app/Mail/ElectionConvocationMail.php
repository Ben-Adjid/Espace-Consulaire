<?php

namespace App\Mail;

use App\Models\Citizen;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Convocation : le vote d'une élection de l'association est ouvert, l'électeur est invité à voter dans son espace. */
class ElectionConvocationMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Citizen $citizen, public string $associationName, public int $year, public string $closesAt, public string $url)
    {
        $this->locale(in_array($citizen->locale, ['fr', 'en', 'ar'], true) ? $citizen->locale : 'fr');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Convocation : vote ouvert').' — '.$this->associationName.' '.$this->year);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.election-convocation', with: [
            'dir' => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',
        ]);
    }
}

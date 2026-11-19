<?php

namespace App\Mail;

use App\Models\Citizen;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Notification de changement de statut associatif : nouveau mandat, ou fin de mandat (retour au statut de citoyen). */
class AssociationRoleMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public Citizen $citizen,
        public string $kind,
        public string $associationName,
        public ?string $roleLabel,
        public ?string $mandateEnd = null,
    ) {
        // Langue du destinataire : sujet et contenu rendus dans sa langue (fr, en, ar).
        $this->locale(in_array($citizen->locale, ['fr', 'en', 'ar'], true) ? $citizen->locale : 'fr');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->kind === 'appointed'
            ? __('Vous êtes désormais membre du bureau')
            : __('Fin de votre mandat associatif'));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.association-role',
            with: [
                'citizen' => $this->citizen,
                'kind' => $this->kind,
                'associationName' => $this->associationName,
                'roleLabel' => $this->roleLabel !== null ? __($this->roleLabel) : null,
                'mandateEnd' => $this->mandateEnd,
                'spaceUrl' => rtrim((string) config('app.frontend_url'), '/').'/'.$this->citizen->consulate?->slug.'/fr/compte/profil',
                'dir' => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',
            ],
        );
    }
}

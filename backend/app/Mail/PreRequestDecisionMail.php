<?php

namespace App\Mail;

use App\Models\Citizen;
use App\Models\PreRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Avis au citoyen : décision du consulat sur sa pré-demande (validée, à compléter, refusée). */
class PreRequestDecisionMail extends Mailable
{
    use SerializesModels;

    public function __construct(public PreRequest $preRequest, public Citizen $citizen)
    {
        // Langue du destinataire : sujet et contenu rendus dans sa langue (fr, en, ar).
        $this->locale(in_array($citizen->locale, ['fr', 'en', 'ar'], true) ? $citizen->locale : 'fr');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Votre pré-demande').' — '.__(PreRequest::STATUSES[$this->preRequest->status] ?? $this->preRequest->status));
    }

    public function content(): Content
    {
        $slug = $this->preRequest->consulate()->withoutGlobalScopes()->value('slug');

        return new Content(
            view: 'emails.pre-request-decision',
            with: [
                'preRequest' => $this->preRequest,
                'citizen' => $this->citizen,
                'statusLabel' => __(PreRequest::STATUSES[$this->preRequest->status] ?? $this->preRequest->status),
                'url' => rtrim((string) config('app.frontend_url'), '/').'/'.$slug.'/'.app()->getLocale().'/compte/demandes/'.$this->preRequest->id,
                'dir' => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',
            ],
        );
    }
}

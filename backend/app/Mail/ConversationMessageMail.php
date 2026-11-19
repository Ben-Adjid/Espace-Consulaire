<?php

namespace App\Mail;

use App\Models\Citizen;
use App\Models\Conversation;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Avis au citoyen : nouveau message du consulat dans sa messagerie.
 * Le contenu du message n'est pas recopié dans l'email : il se lit dans l'espace citoyen (connexion requise).
 */
class ConversationMessageMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Conversation $conversation, public Citizen $citizen)
    {
        $this->locale($this->lang());
    }

    private function lang(): string
    {
        return in_array($this->citizen->locale, ['fr', 'en', 'ar'], true) ? $this->citizen->locale : 'fr';
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Nouveau message du consulat').' — '.$this->conversation->subject);
    }

    public function content(): Content
    {
        $slug = $this->conversation->consulate()->value('slug');

        return new Content(
            view: 'emails.conversation-message',
            with: [
                'citizen' => $this->citizen,
                'conversation' => $this->conversation,
                'url' => rtrim((string) config('app.frontend_url'), '/').'/'.$slug.'/'.$this->lang().'/compte/messages/'.$this->conversation->id,
                'dir' => $this->lang() === 'ar' ? 'rtl' : 'ltr',
            ],
        );
    }
}

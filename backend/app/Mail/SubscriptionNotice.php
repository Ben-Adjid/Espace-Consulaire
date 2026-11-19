<?php

namespace App\Mail;

use App\Models\Consulate;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Rappel au consul : échéance proche, période de grâce, ou suspension pour non-paiement. */
class SubscriptionNotice extends Mailable
{
    use SerializesModels;

    public function __construct(public Consulate $consulate, public string $stage)
    {
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->stage) {
            'suspended' => __('Consulat suspendu : abonnement non renouvelé'),
            'grace' => __('Abonnement échu : délai de grâce en cours'),
            default => __('Abonnement à renouveler'),
        };

        return new Envelope(subject: $subject.' — '.$this->consulate->getTranslation('name', app()->getLocale()));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.subscription-notice', with: [
            'consulateName' => $this->consulate->getTranslation('name', app()->getLocale()),
            'stage' => $this->stage,
            'until' => $this->consulate->subscription_until?->format('d/m/Y'),
            'graceDays' => config('subscription.grace_days'),
        ]);
    }
}

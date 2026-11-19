<?php

namespace App\Actions;

use App\Mail\SubscriptionNotice;
use App\Models\Consulate;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Prévient les consuls de l'abonnement : à l'approche de l'échéance, au début de la grâce, à la suspension.
 * Une seule alerte par étape et par échéance : la dernière étape envoyée est mémorisée sur le consulat,
 * et un nouveau paiement (nouvelle échéance) relance le cycle.
 */
class SendSubscriptionNotices
{
    /** @return int nombre de consulats notifiés */
    public function __invoke(): int
    {
        $notified = 0;

        foreach (Consulate::withoutGlobalScopes()->whereNotNull('subscription_until')->get() as $consulate) {
            $stage = $this->isEligible($consulate) ? $this->stageFor($consulate) : null;
            if ($stage === null) {
                continue;
            }

            $alreadySent = $consulate->subscription_notice_stage === $stage
                && $consulate->subscription_notice_until?->toDateString() === $consulate->subscription_until->toDateString();
            if ($alreadySent) {
                continue;
            }

            $recipients = User::withoutGlobalScopes()
                ->where('consulate_id', $consulate->id)
                ->where('is_active', true)
                ->role(User::ROLE_CONSULATE_ADMIN)
                ->get(['email', 'locale']);

            foreach ($recipients as $recipient) {
                try {
                    Mail::to($recipient->email)->locale($recipient->locale ?: 'fr')->send(new SubscriptionNotice($consulate, $stage));
                } catch (\Throwable $e) {
                    Log::warning('Rappel d’abonnement non envoyé', ['consulate' => $consulate->slug, 'error' => $e->getMessage()]);
                }
            }

            $consulate->forceFill([
                'subscription_notice_stage' => $stage,
                'subscription_notice_until' => $consulate->subscription_until->toDateString(),
            ])->save();
            $notified++;
        }

        return $notified;
    }

    /** Étape à signaler selon l'état réel de l'abonnement, ou null si rien à dire. */
    private function stageFor(Consulate $consulate): ?string
    {
        return match ($consulate->subscriptionState()) {
            'suspended' => 'suspended',
            'grace' => 'grace',
            'expiring' => 'warning',
            default => null,
        };
    }

    /** Un consulat désactivé à la main (sans suspension) n'est pas concerné par les rappels. */
    private function isEligible(Consulate $consulate): bool
    {
        return $consulate->is_active || $consulate->suspended_for_nonpayment;
    }
}

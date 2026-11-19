<?php

namespace App\Console\Commands;

use App\Actions\SendSubscriptionNotices;
use Illuminate\Console\Command;

/** Envoie aux consuls les rappels d'abonnement (échéance proche, grâce, suspension). Une fois par étape. */
class SendSubscriptionReminders extends Command
{
    protected $signature = 'consulat:send-subscription-reminders';

    protected $description = 'Prévient les consuls : abonnement à renouveler, en grâce, ou suspendu.';

    public function handle(SendSubscriptionNotices $notices): int
    {
        $count = $notices();
        $this->info("{$count} consulat(s) notifié(s).");

        return self::SUCCESS;
    }
}

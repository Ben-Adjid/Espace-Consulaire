<?php

namespace App\Console\Commands;

use App\Models\Consulate;
use Illuminate\Console\Command;

/**
 * Suspend les consulats dont l'abonnement est échu depuis plus que la période de grâce.
 * Un consulat sans date d'abonnement n'est pas concerné (abonnement pas encore démarré).
 */
class CheckSubscriptions extends Command
{
    protected $signature = 'consulat:check-subscriptions';

    protected $description = 'Suspend les consulats dont l’abonnement annuel est échu (après la période de grâce).';

    public function handle(): int
    {
        $limit = now()->startOfDay()->subDays(config('subscription.grace_days'));

        $expired = Consulate::withoutGlobalScopes()
            ->where('is_active', true)
            ->whereNotNull('subscription_until')
            ->whereDate('subscription_until', '<', $limit)
            ->get();

        foreach ($expired as $consulate) {
            $consulate->forceFill(['is_active' => false, 'suspended_for_nonpayment' => true])->save();
            $this->line("Suspendu : {$consulate->slug} (abonnement échu le {$consulate->subscription_until->toDateString()})");
        }

        $this->info($expired->count().' consulat(s) suspendu(s).');

        return self::SUCCESS;
    }
}

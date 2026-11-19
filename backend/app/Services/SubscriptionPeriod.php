<?php

namespace App\Services;

use App\Models\Consulate;
use Carbon\CarbonImmutable;

/** Période suivante d'un abonnement : démarre à la fin de la période en cours (ou aujourd'hui), dure un an moins un jour. */
class SubscriptionPeriod
{
    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} début et fin de la période à payer */
    public static function nextFor(Consulate $consulate): array
    {
        $until = $consulate->subscription_until;
        $start = $until && $until->isFuture()
            ? CarbonImmutable::parse($until)->addDay()
            : CarbonImmutable::today();

        return [$start, $start->addYear()->subDay()];
    }
}

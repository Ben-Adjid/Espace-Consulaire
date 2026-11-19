<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Consulate;
use App\Services\GlobalDashboard;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Le consul voit l'état de l'abonnement de SON consulat (lecture seule). */
class SubscriptionStatusWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    // Widget léger : rendu avec la page, pour que l'état de l'abonnement soit visible immédiatement.
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $consulate = auth()->user()?->consulate;
        if (! $consulate instanceof Consulate || auth()->user()?->isSuperAdmin()) {
            return [];
        }

        $state = $consulate->subscriptionState();
        $label = GlobalDashboard::SUBSCRIPTION_LABELS[$state] ?? $state;
        $until = $consulate->subscription_until?->format('d/m/Y');

        $description = match ($state) {
            'active' => "Valable jusqu’au {$until}",
            'expiring' => "À renouveler avant le {$until}",
            'grace' => "Échu le {$until} : délai de grâce en cours",
            'suspended' => __('Consulat suspendu pour non-paiement : contactez la plateforme'),
            default => 'Aucun abonnement enregistré : contactez la plateforme',
        };

        $color = match ($state) {
            'active' => 'success',
            'expiring', 'grace' => 'warning',
            'suspended' => 'danger',
            default => 'gray',
        };

        return [
            Stat::make(__('Abonnement à la plateforme'), $label)
                ->description($description)
                ->color($color),
        ];
    }
}

<?php

namespace App\Filament\Admin\Pages;

use App\Models\ConsulatePayment;
use App\Services\GlobalDashboard;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/** Abonnement de SON consulat : état, échéance, historique des paiements et reçus (consul uniquement). */
class MonAbonnement extends Page
{
    use \App\Filament\Concerns\TranslatedPageLabels;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Paramétrage';

    protected static ?string $navigationLabel = 'Mon abonnement';

    protected static ?string $title = 'Mon abonnement';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.admin.pages.mon-abonnement';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user && ! $user->isSuperAdmin() && $user->hasRole(\App\Models\User::ROLE_CONSULATE_ADMIN) && $user->consulate);
    }

    public function stateLabel(): string
    {
        $consulate = auth()->user()->consulate;

        return GlobalDashboard::SUBSCRIPTION_LABELS[$consulate->subscriptionState()] ?? $consulate->subscriptionState();
    }

    /** @return Collection<int, ConsulatePayment> */
    public function payments(): Collection
    {
        return ConsulatePayment::withoutGlobalScopes()
            ->where('consulate_id', auth()->user()->consulate_id)
            ->latest('paid_at')->get();
    }
}

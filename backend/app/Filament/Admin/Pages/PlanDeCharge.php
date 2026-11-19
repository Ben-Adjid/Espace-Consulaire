<?php

namespace App\Filament\Admin\Pages;

use App\Models\Consulate;
use App\Services\CapacityPlan;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;

/** Plan de charge par service et par mois : places ouvrables face aux rendez-vous pris. */
class PlanDeCharge extends Page
{
    use \App\Filament\Concerns\TranslatedPageLabels;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Accueil du public';

    protected static ?string $navigationLabel = 'Plan de charge';

    protected static ?string $title = 'Plan de charge';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.admin.pages.plan-de-charge';

    public string $month = '';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isSuperAdmin() || ($user?->can('manage_appointments') && $user->consulate_id));
    }

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
    }

    /** @return array<int, array<string, mixed>> */
    public function rows(): array
    {
        $consulate = auth()->user()?->consulate;
        if (! $consulate) {
            return [];
        }

        return app(CapacityPlan::class)->build($consulate, CarbonImmutable::createFromFormat('Y-m', $this->month));
    }
}

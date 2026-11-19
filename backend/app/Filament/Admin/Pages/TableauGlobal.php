<?php

namespace App\Filament\Admin\Pages;

use App\Services\GlobalDashboard;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;

/** Tableau de bord global de la plateforme (super admin uniquement) : tous les consulats côte à côte. */
class TableauGlobal extends Page
{
    use \App\Filament\Concerns\TranslatedPageLabels;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Pilotage';

    protected static ?string $navigationLabel = 'Tableau global';

    protected static ?string $title = 'Tableau de bord global';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.admin.pages.tableau-global';

    public string $month = '';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isSuperAdmin();
    }

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
    }

    /** @return array{rows: array<int, array<string, mixed>>, totals: array<string, int>} */
    public function data(): array
    {
        return app(GlobalDashboard::class)->build(CarbonImmutable::createFromFormat('Y-m', $this->month));
    }
}

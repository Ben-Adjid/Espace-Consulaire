<?php

namespace App\Filament\Admin\Pages;

use App\Models\Consulate;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;

/** Rapport mensuel : choisir le mois, puis télécharger le CSV (par service). */
class RapportMensuel extends Page
{
    use \App\Filament\Concerns\TranslatedPageLabels;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Pilotage';

    protected static ?string $navigationLabel = 'Rapport mensuel';

    protected static ?string $title = 'Rapport mensuel';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.admin.pages.rapport-mensuel';

    public string $month = '';

    public ?int $consulateId = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isSuperAdmin() || ($user?->can('manage_appointments') && $user->consulate_id));
    }

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
        $this->consulateId = auth()->user()?->consulate_id;
    }

    public function downloadUrl(): string
    {
        return route('admin.rapport-mensuel.export', array_filter([
            'month' => $this->month,
            'consulate_id' => auth()->user()?->isSuperAdmin() ? $this->consulateId : null,
        ]));
    }

    /** Version imprimable (PDF via le navigateur), mêmes paramètres que l'export CSV. */
    public function printUrl(): string
    {
        return route('admin.rapport-mensuel.print', array_filter([
            'month' => $this->month,
            'consulate_id' => auth()->user()?->isSuperAdmin() ? $this->consulateId : null,
        ]));
    }

    public function consulateOptions(): array
    {
        return Consulate::withoutGlobalScopes()->orderBy('slug')->get()
            ->mapWithKeys(fn (Consulate $c) => [$c->id => $c->getTranslation('name', 'fr')])->all();
    }
}

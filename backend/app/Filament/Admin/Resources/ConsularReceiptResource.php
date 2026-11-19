<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\HasConsulateField;
use App\Filament\Admin\Resources\ConsularReceiptResource\Pages;
use App\Models\ConsularReceipt;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Journal des encaissements consulaires.
 * Séparation des tâches : l'agent encaisse ; seul l'administrateur du consulat peut annuler un reçu (motif obligatoire).
 */
class ConsularReceiptResource extends Resource
{
    use \App\Filament\Concerns\TranslatedResourceLabels;

    use HasConsulateField;

    protected static ?string $model = ConsularReceipt::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Dossiers et citoyens';

    protected static ?string $modelLabel = 'reçu';

    protected static ?string $pluralModelLabel = 'journal des encaissements';

    protected static ?string $navigationLabel = 'Encaissements';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->can('manage_receipts') || $user?->isSuperAdmin());
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    /** Annulation : administrateur du consulat (permission de suppression) uniquement. */
    public static function canCancel(): bool
    {
        return (bool) auth()->user()?->can('delete_records');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            static::consulateField(),
            Forms\Components\Select::make('service_id')->label(__('Service'))
                ->options(fn () => Service::query()->orderBy('sort_order')->get()
                    ->mapWithKeys(fn (Service $s) => [$s->id => $s->getTranslation('name', 'fr')]))
                ->searchable()
                // Un encaissement doit indiquer la démarche payée, sinon il ne se rattache à aucun tarif.
                ->required(),
            Forms\Components\TextInput::make('payer_name')->label(__('Nom du payeur'))->required()->maxLength(120),
            Forms\Components\TextInput::make('amount')->label(__('Montant'))->numeric()->minValue(0)->required(),
            Forms\Components\TextInput::make('currency')->label(__('Devise'))->default('XOF')->required()->maxLength(8),
            Forms\Components\Select::make('method')->label(__('Mode de paiement'))->options(ConsularReceipt::METHODS)->required(),
            Forms\Components\TextInput::make('reference')->label(__('Référence (transaction, timbre…)'))->maxLength(80),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('received_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('receipt_number')->label(__('N° de reçu'))->searchable()->copyable(),
                Tables\Columns\TextColumn::make('received_at')->label(__('Date'))->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('payer_name')->label(__('Payeur'))->searchable(),
                Tables\Columns\TextColumn::make('service.name')->label(__('Service'))->placeholder(__('—')),
                Tables\Columns\TextColumn::make('amount')->label(__('Montant'))->numeric(0, ',', ' ')
                    ->summarize(Sum::make()->label(__('Total encaissé (hors annulés)'))
                        ->query(fn ($query) => $query->whereNull('cancelled_at'))->numeric(0, ',', ' ')),
                Tables\Columns\TextColumn::make('method')->label(__('Mode'))->formatStateUsing(fn (string $state) => ConsularReceipt::METHODS[$state] ?? $state),
                Tables\Columns\TextColumn::make('cashier.name')->label(__('Encaissé par'))->toggleable(),
                Tables\Columns\TextColumn::make('cancelled_at')->label(__('État'))
                    ->state(fn (ConsularReceipt $r) => $r->isCancelled() ? 'Annulé' : 'Valide')
                    ->badge()->color(fn (string $state) => $state === 'Annulé' ? 'danger' : 'success')
                    ->tooltip(fn (ConsularReceipt $r) => $r->cancel_reason),
                Tables\Columns\TextColumn::make('consulate.slug')->label(__('Consulat'))->badge()
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
            ])
            ->filters([
                Tables\Filters\Filter::make('today')->label(__('Aujourd’hui (clôture de caisse)'))
                    ->query(fn (Builder $q) => $q->whereDate('received_at', today())),
                Tables\Filters\Filter::make('month')->label(__('Ce mois'))
                    ->query(fn (Builder $q) => $q->whereBetween('received_at', [now()->startOfMonth(), now()->endOfMonth()])),
                Tables\Filters\SelectFilter::make('method')->label(__('Mode'))->options(ConsularReceipt::METHODS),
                Tables\Filters\TernaryFilter::make('cancelled')->label(__('Annulés'))
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('cancelled_at'),
                        false: fn (Builder $q) => $q->whereNull('cancelled_at'),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('cancel')->label(__('Annuler le reçu'))->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn (ConsularReceipt $r) => ! $r->isCancelled() && static::canCancel())
                    ->form([Forms\Components\Textarea::make('reason')->label(__('Motif de l’annulation'))->required()->maxLength(500)])
                    ->requiresConfirmation()
                    ->action(fn (ConsularReceipt $r, array $data) => $r->update([
                        'cancelled_at' => now(),
                        'cancel_reason' => $data['reason'],
                        'cancelled_by' => auth()->id(),
                    ])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsularReceipts::route('/'),
            'create' => Pages\CreateConsularReceipt::route('/create'),
        ];
    }
}

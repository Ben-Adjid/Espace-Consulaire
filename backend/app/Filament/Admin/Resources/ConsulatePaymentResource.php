<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ConsulatePaymentResource\Pages;
use App\Models\Consulate;
use App\Models\ConsulatePayment;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Abonnements annuels : le super admin enregistre chaque paiement reçu d'un consulat.
 * Un paiement prolonge l'abonnement ; un consulat suspendu pour non-paiement est réactivé.
 */
class ConsulatePaymentResource extends Resource
{
    use \App\Filament\Concerns\TranslatedResourceLabels;

    protected static ?string $model = ConsulatePayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Pilotage';

    protected static ?string $modelLabel = 'paiement d’abonnement';

    protected static ?string $pluralModelLabel = 'abonnements (paiements)';

    protected static ?string $navigationLabel = 'Abonnements des consulats';

    protected static ?int $navigationSort = 5;

    // Réservé au super admin.
    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->isSuperAdmin();
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return false; // Un paiement enregistré ne se modifie pas : on enregistre une correction.
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('consulate_id')->label(__('Consulat'))
                ->options(fn () => Consulate::withoutGlobalScopes()->orderBy('slug')->get()
                    ->mapWithKeys(fn (Consulate $c) => [$c->id => $c->getTranslation('name', 'fr')]))
                ->required()->searchable()
                ->live()
                ->afterStateUpdated(function (?string $state, Set $set) {
                    // Nouvelle période : à partir du lendemain de la fin actuelle (ou d'aujourd'hui).
                    $c = $state ? Consulate::withoutGlobalScopes()->find($state) : null;
                    $start = $c?->subscription_until && $c->subscription_until->isFuture()
                        ? $c->subscription_until->copy()->addDay()
                        : now();
                    $set('period_start', $start->toDateString());
                    $set('period_end', $start->copy()->addYear()->subDay()->toDateString());
                }),
            Forms\Components\TextInput::make('amount')->label(__('Montant reçu'))->numeric()->minValue(0)->required()
                ->default(fn () => config('subscription.default_amount') ?: null),
            Forms\Components\TextInput::make('currency')->label(__('Devise'))->required()->maxLength(8)
                ->default(fn () => config('subscription.default_currency')),
            Forms\Components\DatePicker::make('paid_at')->label(__('Reçu le'))->required()->default(now())->maxDate(now()),
            Forms\Components\DatePicker::make('period_start')->label(__('Période du'))->required()->live()
                ->afterStateUpdated(fn (?string $state, Set $set) => $state
                    ? $set('period_end', Carbon::parse($state)->addYear()->subDay()->toDateString())
                    : null),
            Forms\Components\DatePicker::make('period_end')->label(__('au'))->required()
                ->afterOrEqual('period_start'),
            Forms\Components\Select::make('method')->label(__('Mode de paiement'))->options(ConsulatePayment::METHODS)->required(),
            Forms\Components\TextInput::make('reference')->label(__('Référence (virement, reçu…)'))->maxLength(80),
            Forms\Components\Textarea::make('notes')->label(__('Notes'))->maxLength(1000)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('paid_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('consulate.slug')->label(__('Consulat'))->badge()->searchable(),
                Tables\Columns\TextColumn::make('amount')->label(__('Montant'))
                    ->formatStateUsing(fn (ConsulatePayment $r) => number_format((float) $r->amount, 0, ',', ' ').' '.$r->currency),
                Tables\Columns\TextColumn::make('paid_at')->label(__('Reçu le'))->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('period_start')->label(__('Période'))
                    ->formatStateUsing(fn (ConsulatePayment $r) => $r->period_start->format('d/m/Y').' → '.$r->period_end->format('d/m/Y')),
                Tables\Columns\TextColumn::make('method')->label(__('Mode'))->formatStateUsing(fn (string $state) => ConsulatePayment::METHODS[$state] ?? $state),
                Tables\Columns\TextColumn::make('reference')->label(__('Référence'))->toggleable(),
                Tables\Columns\TextColumn::make('recorder.name')->label(__('Enregistré par'))->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('consulate_id')->label(__('Consulat'))
                    ->options(fn () => Consulate::withoutGlobalScopes()->pluck('slug', 'id')),
            ])
            ->actions([
                Tables\Actions\Action::make('receipt')->label(__('Reçu'))->icon('heroicon-o-printer')->color('gray')
                    ->url(fn ($record) => route('admin.paiements.recu', $record), shouldOpenInNewTab: true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsulatePayments::route('/'),
            'create' => Pages\CreateConsulatePayment::route('/create'),
        ];
    }
}

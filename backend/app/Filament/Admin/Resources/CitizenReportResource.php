<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CitizenReportResource\Pages;
use App\Models\CitizenReport;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Signalements des citoyens. Les signalements d'intégrité (fraude, corruption) n'apparaissent
 * que pour le super admin : le personnel du consulat ne peut ni les voir ni les traiter.
 */
class CitizenReportResource extends Resource
{
    use \App\Filament\Concerns\TranslatedResourceLabels;

    protected static ?string $model = CitizenReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationLabel = 'Signalements';

    protected static ?string $navigationGroup = 'Dossiers et citoyens';

    protected static ?string $modelLabel = 'signalement';

    protected static ?string $pluralModelLabel = 'signalements';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isSuperAdmin() || $user?->can('manage_messages'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user())->with('citizen');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', 'new')->count();

        return $count ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label(__('Reçu le'))->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('category')->label(__('Catégorie'))
                    ->formatStateUsing(fn (string $state) => CitizenReport::CATEGORIES[$state] ?? $state)->badge()
                    ->color(fn (string $state) => $state === CitizenReport::CATEGORY_INTEGRITY ? 'danger' : 'gray'),
                Tables\Columns\TextColumn::make('subject')->label(__('Objet'))->searchable()->wrap(),
                Tables\Columns\TextColumn::make('citizen.full_name')->label(__('Citoyen'))->placeholder(__('—')),
                Tables\Columns\TextColumn::make('status')->label(__('Statut'))
                    ->formatStateUsing(fn (string $state) => CitizenReport::STATUSES[$state] ?? $state)->badge()
                    ->color(fn (string $state) => match ($state) {
                        'new' => 'warning',
                        'in_progress' => 'info',
                        'resolved' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('consulate.slug')->label(__('Consulat'))->badge()
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label(__('Statut'))->options(CitizenReport::STATUSES),
                Tables\Filters\SelectFilter::make('category')->label(__('Catégorie'))->options(fn () => auth()->user()?->isSuperAdmin()
                    ? CitizenReport::CATEGORIES
                    : collect(CitizenReport::CATEGORIES)->except(CitizenReport::CATEGORY_INTEGRITY)->all()),
            ])
            ->actions([
                Tables\Actions\Action::make('respond')->label(__('Traiter'))->icon('heroicon-o-chat-bubble-left-right')
                    ->modalHeading(fn (CitizenReport $r) => $r->subject)
                    ->modalDescription(fn (CitizenReport $r) => $r->message)
                    ->fillForm(fn (CitizenReport $r) => ['status' => $r->status, 'response' => $r->response])
                    ->form([
                        Forms\Components\Select::make('status')->label(__('Statut'))->options(CitizenReport::STATUSES)->required(),
                        Forms\Components\Textarea::make('response')->label(__('Réponse au citoyen (visible dans son espace)'))->maxLength(2000),
                    ])
                    ->action(fn (CitizenReport $r, array $data) => $r->update([
                        'status' => $data['status'],
                        'response' => $data['response'] ?? null,
                        'responded_at' => filled($data['response'] ?? null) ? now() : $r->responded_at,
                        'handled_by' => auth()->id(),
                    ])),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListCitizenReports::route('/')];
    }
}

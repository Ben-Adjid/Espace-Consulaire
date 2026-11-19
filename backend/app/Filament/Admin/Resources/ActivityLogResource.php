<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogResource extends Resource
{
    use \App\Filament\Concerns\TranslatedResourceLabels;

    protected static ?string $model = ActivityLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Journal d’activité';

    protected static ?string $navigationGroup = 'Pilotage';

    protected static ?string $modelLabel = 'entrée du journal';

    protected static ?string $pluralModelLabel = 'journal d’activité';

    protected static ?int $navigationSort = 3;

    /** Outil de supervision : super administrateur et consul (gestion de l'équipe) ; pas les agents. */
    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isSuperAdmin() || $user?->can('manage_users'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['actor', 'consulate']);
        $user = auth()->user();

        if ($user && ! $user->isSuperAdmin()) {
            $query->where('consulate_id', $user->consulate_id ?? 0);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label(__('Date'))->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('actor.name')->label(__('Utilisateur'))->placeholder(__('Compte supprimé')),
                Tables\Columns\TextColumn::make('action')->label(__('Action'))->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'created' => __('Création'),
                        'updated' => 'Modification',
                        'deleted' => 'Suppression',
                        'role_assigned' => __('Rôle attribué'),
                        'role_updated' => __('Rôle modifié'),
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('subject_type')->label(__('Élément'))
                    ->formatStateUsing(fn (string $state) => class_basename($state)),
                Tables\Columns\TextColumn::make('subject_id')->label(__('Identifiant')),
                Tables\Columns\TextColumn::make('changed_fields')->label(__('Champs modifiés'))
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : ''),
                Tables\Columns\TextColumn::make('consulate.slug')->label(__('Consulat'))
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')->label(__('Action'))->options([
                    'created' => __('Création'),
                    'updated' => 'Modification',
                    'deleted' => 'Suppression',
                    'role_assigned' => __('Rôle attribué'),
                    'role_updated' => __('Rôle modifié'),
                ]),
                // Période : du / au (date de l'action).
                Tables\Filters\Filter::make('period')->label(__('Période'))
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label(__('Du')),
                        \Filament\Forms\Components\DatePicker::make('until')->label(__('Au')),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d))),
                // Type d'élément touché (citoyen, pré-demande, association…), limité à ceux présents dans le journal.
                Tables\Filters\SelectFilter::make('subject_type')->label(__('Élément'))
                    ->options(fn () => ActivityLog::query()->distinct()->pluck('subject_type')
                        ->mapWithKeys(fn ($type) => [$type => class_basename((string) $type)])->all()),
                // Auteur de l'action (agent ou membre du bureau), limité aux comptes ayant agi.
                Tables\Filters\SelectFilter::make('actor_id')->label(__('Utilisateur'))
                    ->options(fn () => \App\Models\User::query()->withoutGlobalScopes()
                        ->whereIn('id', ActivityLog::query()->whereNotNull('actor_id')->distinct()->pluck('actor_id'))
                        ->pluck('name', 'id')->all())
                    ->searchable(),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}

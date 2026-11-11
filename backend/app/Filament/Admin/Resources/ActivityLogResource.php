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
    protected static ?string $model = ActivityLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $modelLabel = 'entrée du journal';

    protected static ?string $pluralModelLabel = 'journal d’activité';

    protected static ?int $navigationSort = 3;

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
                Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('actor.name')->label('Utilisateur')->placeholder('Compte supprimé'),
                Tables\Columns\TextColumn::make('action')->label('Action')->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'created' => 'Création',
                        'updated' => 'Modification',
                        'deleted' => 'Suppression',
                        'role_assigned' => 'Rôle attribué',
                        'role_updated' => 'Rôle modifié',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('subject_type')->label('Élément')
                    ->formatStateUsing(fn (string $state) => class_basename($state)),
                Tables\Columns\TextColumn::make('subject_id')->label('Identifiant'),
                Tables\Columns\TextColumn::make('changed_fields')->label('Champs modifiés')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : ''),
                Tables\Columns\TextColumn::make('consulate.slug')->label('Consulat')
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')->label('Action')->options([
                    'created' => 'Création',
                    'updated' => 'Modification',
                    'deleted' => 'Suppression',
                    'role_assigned' => 'Rôle attribué',
                    'role_updated' => 'Rôle modifié',
                ]),
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

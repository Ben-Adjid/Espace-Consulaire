<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\HasConsulateField;
use App\Filament\Admin\Resources\StaffAbsenceResource\Pages;
use App\Models\StaffAbsence;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Absences de l'équipe (congé, mission, formation). Le consul les saisit ; tous les voient,
 * et elles apparaissent dans le calendrier des rendez-vous pour ajuster l'accueil.
 */
class StaffAbsenceResource extends Resource
{
    use \App\Filament\Concerns\TranslatedResourceLabels;
    use HasConsulateField;

    protected static ?string $model = StaffAbsence::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-minus';

    protected static ?string $navigationGroup = 'Paramétrage';

    protected static ?string $navigationLabel = 'Absences de l’équipe';

    protected static ?string $modelLabel = 'absence';

    protected static ?string $pluralModelLabel = 'absences';

    protected static ?int $navigationSort = 8;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isSuperAdmin() || $user?->can('manage_appointments'));
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isSuperAdmin() || $user?->can('manage_users'));
    }

    public static function canEdit($record): bool
    {
        return static::canCreate();
    }

    public static function canDelete($record): bool
    {
        return static::canCreate();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            static::consulateField(),
            Forms\Components\Select::make('user_id')->label(__('Membre de l’équipe'))->required()->searchable()
                // Uniquement les membres du consulat (le super admin voit tous les consulats).
                ->options(fn () => User::query()
                    ->when(! auth()->user()?->isSuperAdmin(), fn ($q) => $q->where('consulate_id', auth()->user()?->consulate_id))
                    ->whereNotNull('consulate_id')->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all()),
            Forms\Components\DatePicker::make('starts_on')->label(__('Du'))->required(),
            Forms\Components\DatePicker::make('ends_on')->label(__('Au'))->required()->afterOrEqual('starts_on'),
            Forms\Components\TextInput::make('reason')->label(__('Motif (facultatif)'))->maxLength(160)
                ->helperText(__('Ex. congé annuel, mission, formation. Visible par l’équipe uniquement.')),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_on', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label(__('Membre de l’équipe'))->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('starts_on')->label(__('Du'))->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('ends_on')->label(__('Au'))->date('d/m/Y'),
                Tables\Columns\TextColumn::make('reason')->label(__('Motif'))->placeholder('—'),
                Tables\Columns\TextColumn::make('consulate.slug')->label(__('Consulat'))->badge()
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
            ])
            ->filters([
                Tables\Filters\Filter::make('current')->label(__('En cours ou à venir'))->toggle()->default()
                    ->query(fn (Builder $q) => $q->whereDate('ends_on', '>=', today())),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading(__('Aucune absence enregistrée'));
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageStaffAbsences::route('/')];
    }
}

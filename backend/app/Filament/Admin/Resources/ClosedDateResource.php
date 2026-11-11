<?php

namespace App\Filament\Admin\Resources;

use App\Enums\AppointmentStatus;
use App\Filament\Admin\Concerns\HasConsulateField;
use App\Filament\Admin\Resources\ClosedDateResource\Pages;
use App\Models\Appointment;
use App\Models\ClosedDate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** Fermetures exceptionnelles : jours fériés, travaux. Les créneaux de ces jours ne sont plus proposés. */
class ClosedDateResource extends Resource
{
    use HasConsulateField;

    protected static ?string $model = ClosedDate::class;

    protected static ?string $navigationIcon = 'heroicon-o-no-symbol';

    protected static ?string $navigationGroup = 'Rendez-vous';

    protected static ?string $modelLabel = 'fermeture';

    protected static ?string $pluralModelLabel = 'fermetures';

    protected static ?int $navigationSort = 4;

    /** Nombre de rendez-vous encore actifs ce jour-là pour le consulat. */
    public static function affectedAppointments(ClosedDate $closed): int
    {
        return Appointment::withoutGlobalScopes()
            ->where('consulate_id', $closed->consulate_id)
            ->whereDate('appointment_date', $closed->date)
            ->whereIn('status', AppointmentStatus::occupying())
            ->count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                static::consulateField(),
                Forms\Components\DatePicker::make('date')->label('Jour fermé')->required()->native(false),
                Forms\Components\TextInput::make('label')->label('Motif')->required()->maxLength(160)
                    ->placeholder('Fête nationale'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('date')->columns([
            Tables\Columns\TextColumn::make('date')->label('Jour')->date('d/m/Y')->sortable(),
            Tables\Columns\TextColumn::make('label')->label('Motif')->searchable(),
            Tables\Columns\TextColumn::make('consulate.slug')->label('Consulat')->badge()
                ->visible(fn () => auth()->user()?->isSuperAdmin()),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClosedDates::route('/'),
            'create' => Pages\CreateClosedDate::route('/create'),
            'edit' => Pages\EditClosedDate::route('/{record}/edit'),
        ];
    }
}

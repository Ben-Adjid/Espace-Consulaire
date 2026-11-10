<?php

namespace App\Filament\Admin\Resources;

use App\Enums\AppointmentStatus;
use App\Filament\Admin\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Rendez-vous';

    protected static ?string $modelLabel = 'rendez-vous';

    protected static ?string $pluralModelLabel = 'rendez-vous';

    protected static ?int $navigationSort = 1;

    /** Les rendez-vous sont créés par le public ; l'admin gère leur statut. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Appointment::query()->where('status', AppointmentStatus::Pending->value)->count();

        return $count ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Demande')->columns(2)->schema([
                Forms\Components\TextInput::make('reference')->label('Référence')->disabled(),
                Forms\Components\Select::make('status')->label('Statut')
                    ->options(AppointmentStatus::class)->required()->native(false),
                Forms\Components\DatePicker::make('appointment_date')->label('Date')->disabled(),
                Forms\Components\TextInput::make('start_time')->label('Heure')->disabled()
                    ->formatStateUsing(fn ($state) => substr((string) $state, 0, 5)),
                Forms\Components\TextInput::make('full_name')->label('Demandeur')->disabled(),
                Forms\Components\TextInput::make('phone')->label('Téléphone')->disabled(),
                Forms\Components\TextInput::make('email')->label('Email')->disabled(),
                Forms\Components\Textarea::make('notes')->label('Notes / message')->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $q) => $q->with(['service']))
            ->defaultSort('appointment_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('reference')->label('Référence')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('appointment_date')->label('Date')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('start_time')->label('Heure')
                    ->formatStateUsing(fn ($state) => substr((string) $state, 0, 5)),
                Tables\Columns\TextColumn::make('service.name')->label('Service'),
                Tables\Columns\TextColumn::make('full_name')->label('Demandeur')->searchable(),
                Tables\Columns\TextColumn::make('phone')->label('Téléphone'),
                Tables\Columns\TextColumn::make('consulate.slug')->label('Consulat')->badge()
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
                Tables\Columns\TextColumn::make('status')->label('Statut')->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Statut')->options(AppointmentStatus::class),
                Tables\Filters\Filter::make('today')->label("Aujourd'hui")
                    ->query(fn (Builder $q) => $q->whereDate('appointment_date', today())),
                Tables\Filters\Filter::make('upcoming')->label('À venir')
                    ->query(fn (Builder $q) => $q->whereDate('appointment_date', '>=', today())),
            ])
            ->actions([
                Tables\Actions\Action::make('confirm')->label('Confirmer')->icon('heroicon-o-check')->color('success')
                    ->visible(fn (Appointment $r) => $r->status === AppointmentStatus::Pending)
                    ->action(fn (Appointment $r) => $r->update(['status' => AppointmentStatus::Confirmed, 'confirmed_at' => now()])),
                Tables\Actions\Action::make('complete')->label('Terminer')->icon('heroicon-o-flag')->color('gray')
                    ->visible(fn (Appointment $r) => $r->status === AppointmentStatus::Confirmed)
                    ->action(fn (Appointment $r) => $r->update(['status' => AppointmentStatus::Completed])),
                Tables\Actions\Action::make('cancel')->label('Annuler')->icon('heroicon-o-x-mark')->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Appointment $r) => in_array($r->status, [AppointmentStatus::Pending, AppointmentStatus::Confirmed], true))
                    ->action(fn (Appointment $r) => $r->update(['status' => AppointmentStatus::Cancelled])),
                Tables\Actions\EditAction::make()->label('Détail'),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('confirm')->label('Confirmer la sélection')->icon('heroicon-o-check')
                    ->action(fn ($records) => $records->each->update(['status' => AppointmentStatus::Confirmed, 'confirmed_at' => now()]))
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}

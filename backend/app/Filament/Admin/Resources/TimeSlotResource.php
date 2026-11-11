<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\HasConsulateField;
use App\Filament\Admin\Resources\TimeSlotResource\Pages;
use App\Models\Service;
use App\Models\TimeSlot;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** Créneaux hebdomadaires récurrents : jour + plage horaire + capacité. */
class TimeSlotResource extends Resource
{
    use HasConsulateField;

    protected static ?string $model = TimeSlot::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Rendez-vous';

    protected static ?string $modelLabel = 'créneau';

    protected static ?string $pluralModelLabel = 'créneaux horaires';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                static::consulateField()->live(),
                Forms\Components\Select::make('weekday')->label('Jour de la semaine')
                    ->options(TimeSlot::WEEKDAYS)->required(),
                Forms\Components\TimePicker::make('start_time')->label('Début')->seconds(false)->required(),
                Forms\Components\TimePicker::make('end_time')->label('Fin')->seconds(false)->required()->after('start_time'),
                Forms\Components\TextInput::make('capacity')->label('Capacité (rendez-vous simultanés)')
                    ->numeric()->minValue(1)->maxValue(500)->default(1)->required(),
                Forms\Components\Select::make('service_id')->label('Service concerné')
                    ->placeholder('Tous les services')
                    ->options(function (Forms\Get $get) {
                        $user = auth()->user();
                        $query = Service::query();
                        if ($user?->isSuperAdmin() && $get('consulate_id')) {
                            $query->where('consulate_id', $get('consulate_id'));
                        }

                        return $query->ordered()->get()
                            ->mapWithKeys(fn (Service $s) => [$s->id => $s->getTranslation('name', 'fr')])->all();
                    }),
                Forms\Components\Toggle::make('is_active')->label('Actif')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('weekday')->columns([
            Tables\Columns\TextColumn::make('weekday')->label('Jour')
                ->formatStateUsing(fn ($state) => TimeSlot::WEEKDAYS[$state] ?? $state)->sortable(),
            Tables\Columns\TextColumn::make('start_time')->label('Horaire')
                ->formatStateUsing(fn ($state, TimeSlot $record) => $record->label()),
            Tables\Columns\TextColumn::make('capacity')->label('Capacité'),
            Tables\Columns\TextColumn::make('service.name')->label('Service')->placeholder('Tous'),
            Tables\Columns\TextColumn::make('consulate.slug')->label('Consulat')->badge()
                ->visible(fn () => auth()->user()?->isSuperAdmin()),
            Tables\Columns\IconColumn::make('is_active')->label('Actif')->boolean(),
        ])->filters([
            Tables\Filters\SelectFilter::make('weekday')->label('Jour')->options(TimeSlot::WEEKDAYS),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTimeSlots::route('/'),
            'create' => Pages\CreateTimeSlot::route('/create'),
            'edit' => Pages\EditTimeSlot::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\HasConsulateField;
use App\Filament\Admin\Resources\ServiceResource\Pages;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ServiceResource extends Resource
{
    use \App\Filament\Concerns\TranslatedResourceLabels;

    use HasConsulateField, Translatable;

    public const ICONS = [
        'passport' => 'Passeport', 'id-card' => 'Carte', 'visa' => 'Visa', 'stamp' => 'Légalisation',
        'users' => 'État civil', 'pen' => 'Procuration', 'plane' => 'Laissez-passer', 'document' => 'Document',
    ];

    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Services';

    protected static ?string $navigationGroup = 'Paramétrage';

    protected static ?string $modelLabel = 'service';

    protected static ?string $pluralModelLabel = 'services consulaires';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                static::consulateField(),
                Forms\Components\TextInput::make('name')->label(__('Nom du service'))->required()->maxLength(160)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get, $operation) {
                        if ($operation === 'create' && ! $get('slug')) {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                Forms\Components\TextInput::make('slug')->label(__('Identifiant URL'))->required()->alphaDash()->maxLength(120),
                Forms\Components\Select::make('icon')->label(__('Icône'))->options(self::ICONS)->default('document')->required(),
                Forms\Components\TextInput::make('fee')->label(__('Frais'))->maxLength(80)->placeholder(__('45 000 FCFA')),
                Forms\Components\TextInput::make('processing_time')->label(__('Délai de traitement'))->maxLength(120),
                // Délai cible en jours : au-delà, une pré-demande en attente est signalée « en retard ».
                Forms\Components\TextInput::make('target_days')->label(__('Délai cible de traitement (jours)'))->numeric()->minValue(1)->maxValue(365)
                    ->helperText(__('Au-delà, une pré-demande en attente est signalée en retard. Laisser vide : pas de suivi de délai.')),
                // Validité minimale du passeport (ex. visa) : renseignée par le consulat, jamais imposée par défaut.
                Forms\Components\TextInput::make('min_passport_validity_months')->label(__('Passeport valable au moins (mois)'))
                    ->numeric()->minValue(1)->maxValue(120)
                    ->helperText(__('Laisser vide si le service n’exige pas de validité minimale du passeport.')),
                // Règle de rendez-vous : un service qui l'exige ne peut être réservé qu'avec un dossier en ligne validé.
                Forms\Components\Toggle::make('requires_validated_file')->label(__('Rendez-vous seulement avec un dossier validé'))
                    ->default(false)
                    ->helperText(__('Si activé, le citoyen doit d’abord avoir un dossier en ligne validé pour ce service.')),
                Forms\Components\TextInput::make('sort_order')->label(__('Ordre'))->numeric()->default(0),
                Forms\Components\Textarea::make('summary')->label(__('Résumé (liste)'))->rows(2)->maxLength(300)->columnSpanFull(),
                Forms\Components\Textarea::make('description')->label(__('Description détaillée'))->rows(6)->columnSpanFull(),
                Forms\Components\Textarea::make('required_documents')->label(__('Documents requis'))
                    ->helperText(__('Un document par ligne. Pour une pièce qui ne concerne qu’une situation, commencez la ligne par une étiquette : [mineur], [majeur], [premiere] ou [renouvellement]. Exemple : « [mineur] Autorisation parentale signée ». Sans étiquette, la pièce concerne tout le monde.'))
                    ->rows(8)->columnSpanFull(),
                Forms\Components\Toggle::make('allow_appointment')->label(__('Autoriser la prise de rendez-vous'))->default(true),
                Forms\Components\Toggle::make('is_active')->label(__('Publié'))->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sort_order')->columns([
            Tables\Columns\TextColumn::make('name')->label(__('Service'))->searchable(),
            Tables\Columns\TextColumn::make('consulate.slug')->label(__('Consulat'))->badge()
                ->visible(fn () => auth()->user()?->isSuperAdmin()),
            Tables\Columns\TextColumn::make('fee')->label(__('Frais')),
            Tables\Columns\IconColumn::make('allow_appointment')->label(__('RDV'))->boolean(),
            Tables\Columns\IconColumn::make('is_active')->label(__('Publié'))->boolean(),
            Tables\Columns\TextColumn::make('sort_order')->label(__('Ordre'))->sortable(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Admin\Resources\ServiceResource\RelationManagers\FeesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}

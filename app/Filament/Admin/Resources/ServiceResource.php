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
    use HasConsulateField, Translatable;

    public const ICONS = [
        'passport' => 'Passeport', 'id-card' => 'Carte', 'visa' => 'Visa', 'stamp' => 'Légalisation',
        'users' => 'État civil', 'pen' => 'Procuration', 'plane' => 'Laissez-passer', 'document' => 'Document',
    ];

    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Contenu';

    protected static ?string $modelLabel = 'service';

    protected static ?string $pluralModelLabel = 'services consulaires';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                static::consulateField(),
                Forms\Components\TextInput::make('name')->label('Nom du service')->required()->maxLength(160)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get, $operation) {
                        if ($operation === 'create' && ! $get('slug')) {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                Forms\Components\TextInput::make('slug')->label('Identifiant URL')->required()->alphaDash()->maxLength(120),
                Forms\Components\Select::make('icon')->label('Icône')->options(self::ICONS)->default('document')->required(),
                Forms\Components\TextInput::make('fee')->label('Frais (fictifs)')->maxLength(80)->placeholder('45 000 FCFA'),
                Forms\Components\TextInput::make('processing_time')->label('Délai de traitement')->maxLength(120),
                Forms\Components\TextInput::make('sort_order')->label('Ordre')->numeric()->default(0),
                Forms\Components\Textarea::make('summary')->label('Résumé (liste)')->rows(2)->maxLength(300)->columnSpanFull(),
                Forms\Components\Textarea::make('description')->label('Description détaillée')->rows(6)->columnSpanFull(),
                Forms\Components\Textarea::make('required_documents')->label('Documents requis')
                    ->helperText('Un document par ligne : ils s’affichent en checklist sur le site.')
                    ->rows(8)->columnSpanFull(),
                Forms\Components\Toggle::make('allow_appointment')->label('Autoriser la prise de rendez-vous')->default(true),
                Forms\Components\Toggle::make('is_active')->label('Publié')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sort_order')->columns([
            Tables\Columns\TextColumn::make('name')->label('Service')->searchable(),
            Tables\Columns\TextColumn::make('consulate.slug')->label('Consulat')->badge()
                ->visible(fn () => auth()->user()?->isSuperAdmin()),
            Tables\Columns\TextColumn::make('fee')->label('Frais'),
            Tables\Columns\IconColumn::make('allow_appointment')->label('RDV')->boolean(),
            Tables\Columns\IconColumn::make('is_active')->label('Publié')->boolean(),
            Tables\Columns\TextColumn::make('sort_order')->label('Ordre')->sortable(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
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

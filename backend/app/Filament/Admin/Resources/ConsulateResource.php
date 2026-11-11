<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ConsulateResource\Pages;
use App\Models\Consulate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * super_admin : liste / création / édition de tous les consulats.
 * consulate_admin : voit uniquement SON consulat (« Mon consulat »), sans création ni suppression.
 */
class ConsulateResource extends Resource
{
    use Translatable;

    protected static ?string $model = Consulate::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $modelLabel = 'consulat';

    protected static ?string $pluralModelLabel = 'consulats';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return auth()->user()?->isSuperAdmin() ? 'Consulats' : 'Mon consulat';
    }

    public static function form(Form $form): Form
    {
        $isSuper = fn () => auth()->user()?->isSuperAdmin();

        return $form->schema([
            Forms\Components\Section::make('Identité')->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label('Nom du consulat')->required()->maxLength(160),
                Forms\Components\TextInput::make('consul_name')->label('Nom du consul (fictif)')->maxLength(120),
                Forms\Components\TextInput::make('represented_country')->label('Pays représenté')->required(),
                Forms\Components\TextInput::make('host_country')->label("Pays d'accueil")->required(),
                Forms\Components\TextInput::make('slug')->label('Identifiant URL (slug)')
                    ->required()->alphaDash()->unique(ignoreRecord: true)
                    ->helperText('Adresse publique : /{slug}')->visible($isSuper),
                Forms\Components\TextInput::make('ref_prefix')->label('Préfixe des références RDV')
                    ->required()->maxLength(8)->default('CONS')->visible($isSuper),
                Forms\Components\CheckboxList::make('languages')->label('Langues disponibles')
                    ->options(['fr' => 'Français', 'en' => 'English', 'ar' => 'العربية'])
                    ->default(['fr'])->required()->columns(3),
                Forms\Components\Toggle::make('is_active')->label('Consulat actif (site public en ligne)')
                    ->default(true)->visible($isSuper),
            ]),
            Forms\Components\Section::make('Apparence')->columns(2)->schema([
                Forms\Components\FileUpload::make('logo')->label('Logo (image neutre, pas d’armoirie officielle)')
                    ->image()->disk('public')->directory('logos')->maxSize(1024)->columnSpanFull(),
                Forms\Components\ColorPicker::make('primary_color')->label('Couleur principale')->required()->default('#1d4ed8'),
                Forms\Components\ColorPicker::make('secondary_color')->label('Couleur secondaire')->required()->default('#facc15'),
                Forms\Components\ColorPicker::make('accent_color')->label('Couleur d’accent')->required()->default('#dc2626'),
            ]),
            Forms\Components\Section::make('Page d’accueil')->schema([
                Forms\Components\TextInput::make('hero_title')->label('Titre principal'),
                Forms\Components\Textarea::make('hero_subtitle')->label('Sous-titre')->rows(2),
                Forms\Components\Textarea::make('consul_message')->label('Mot du consul')->rows(6),
                Forms\Components\Textarea::make('mission')->label('Mission du consulat')->rows(4),
            ]),
            Forms\Components\Section::make('Coordonnées et horaires')->columns(2)->schema([
                Forms\Components\Textarea::make('address')->label('Adresse')->rows(2)->columnSpanFull(),
                Forms\Components\TextInput::make('phone')->label('Téléphone')->tel()->maxLength(40),
                Forms\Components\TextInput::make('email')->label('Email')->email()->maxLength(120),
                Forms\Components\TextInput::make('whatsapp')->label('WhatsApp (format international)')->maxLength(40),
                Forms\Components\TextInput::make('latitude')->label('Latitude')->numeric()->minValue(-90)->maxValue(90),
                Forms\Components\TextInput::make('longitude')->label('Longitude')->numeric()->minValue(-180)->maxValue(180),
                Forms\Components\Textarea::make('opening_hours')->label("Horaires d'ouverture (une ligne par jour)")
                    ->rows(5)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ColorColumn::make('primary_color')->label(''),
            Tables\Columns\TextColumn::make('name')->label('Nom')->searchable(),
            Tables\Columns\TextColumn::make('slug')->label('URL')->badge(),
            Tables\Columns\TextColumn::make('languages')->label('Langues')->badge(),
            Tables\Columns\IconColumn::make('is_active')->label('Actif')->boolean(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\Action::make('site')->label('Voir le site')->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (Consulate $record) => url('/'.$record->slug), shouldOpenInNewTab: true),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsulates::route('/'),
            'create' => Pages\CreateConsulate::route('/create'),
            'edit' => Pages\EditConsulate::route('/{record}/edit'),
        ];
    }
}

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
    use \App\Filament\Concerns\TranslatedResourceLabels;

    use Translatable;

    protected static ?string $model = Consulate::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Paramétrage';

    /** La création d'un consulat se fait uniquement sur la page « Nouveau consulat » (modèle + comptes). */
    public static function canCreate(): bool
    {
        return false;
    }

    protected static ?string $modelLabel = 'consulat';

    protected static ?string $pluralModelLabel = 'consulats';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return auth()->user()?->isSuperAdmin() ? __('Consulats') : __('Mon consulat');
    }

    public static function form(Form $form): Form
    {
        $isSuper = fn () => auth()->user()?->isSuperAdmin();

        return $form->schema([
            Forms\Components\Section::make('Identité')->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label(__('Nom du consulat'))->required()->maxLength(160),
                Forms\Components\TextInput::make('consul_name')->label(__('Nom du consul'))->maxLength(120),
                Forms\Components\TextInput::make('represented_country')->label(__('Pays représenté'))->required(),
                Forms\Components\TextInput::make('host_country')->label("Pays d'accueil")->required(),
                Forms\Components\TextInput::make('slug')->label(__('Identifiant URL (slug)'))
                    ->required()->alphaDash()->unique(ignoreRecord: true)
                    ->helperText(__('Adresse publique : /{slug}'))->visible($isSuper),
                Forms\Components\TextInput::make('ref_prefix')->label(__('Préfixe des références RDV'))
                    ->required()->maxLength(8)->default('CONS')->visible($isSuper),
                Forms\Components\CheckboxList::make('languages')->label(__('Langues disponibles'))
                    ->options(['fr' => __('Français'), 'en' => 'English', 'ar' => 'العربية'])
                    ->default(['fr'])->required()->columns(3),
                Forms\Components\TagsInput::make('covered_countries')->label(__('Pays de résidence couverts'))
                    ->placeholder(__('Ajouter un pays puis Entrée'))
                    ->helperText(__('Les citoyens résidant dans ces pays sont rattachés à ce consulat. Un pays ne doit être couvert que par un seul consulat.'))
                    ->columnSpanFull()->visible($isSuper),
                Forms\Components\Toggle::make('is_active')->label(__('Consulat actif (site public en ligne)'))
                    ->default(true)->visible($isSuper),
            ]),
            Forms\Components\Section::make('Apparence')->columns(2)->schema([
                Forms\Components\FileUpload::make('logo')->label(__('Logo (image neutre, pas d’armoirie officielle)'))
                    ->image()->disk('public')->directory('logos')->maxSize(1024)->columnSpanFull(),
                Forms\Components\ColorPicker::make('primary_color')->label(__('Couleur principale'))->required()->default('#1d4ed8'),
                Forms\Components\ColorPicker::make('secondary_color')->label(__('Couleur secondaire'))->required()->default('#facc15'),
                Forms\Components\ColorPicker::make('accent_color')->label(__('Couleur d’accent'))->required()->default('#dc2626'),
            ]),
            Forms\Components\Section::make('Page d’accueil')->schema([
                Forms\Components\TextInput::make('hero_title')->label(__('Titre principal')),
                Forms\Components\Textarea::make('hero_subtitle')->label(__('Sous-titre'))->rows(2),
                Forms\Components\Textarea::make('consul_message')->label(__('Mot du consul'))->rows(6),
                Forms\Components\Textarea::make('mission')->label(__('Mission du consulat'))->rows(4),
            ]),
            Forms\Components\Section::make('Coordonnées et horaires')->columns(2)->schema([
                Forms\Components\Textarea::make('address')->label(__('Adresse'))->rows(2)->columnSpanFull(),
                Forms\Components\TextInput::make('phone')->label(__('Téléphone'))->tel()->maxLength(40),
                Forms\Components\TextInput::make('email')->label(__('Email'))->email()->maxLength(120),
                Forms\Components\TextInput::make('whatsapp')->label(__('WhatsApp (format international)'))->maxLength(40),
                Forms\Components\TextInput::make('latitude')->label(__('Latitude'))->numeric()->minValue(-90)->maxValue(90),
                Forms\Components\TextInput::make('longitude')->label(__('Longitude'))->numeric()->minValue(-180)->maxValue(180),
                Forms\Components\Textarea::make('opening_hours')->label("Horaires d'ouverture (une ligne par jour)")
                    ->rows(5)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ColorColumn::make('primary_color')->label(''),
            Tables\Columns\TextColumn::make('name')->label(__('Nom'))->searchable(),
            Tables\Columns\TextColumn::make('slug')->label(__('URL'))->badge(),
            Tables\Columns\TextColumn::make('languages')->label(__('Langues'))->badge(),
            Tables\Columns\IconColumn::make('is_active')->label(__('Actif'))->boolean(),
            Tables\Columns\TextColumn::make('subscription_until')->label(__('Abonnement'))
                ->state(fn (Consulate $r) => $r->subscriptionState())
                ->formatStateUsing(fn (Consulate $r, string $state) => match ($state) {
                    'active' => 'À jour (jusqu’au '.$r->subscription_until->format('d/m/Y').')',
                    'expiring' => 'À renouveler ('.$r->subscription_until->format('d/m/Y').')',
                    'grace' => 'Échu le '.$r->subscription_until->format('d/m/Y').' (grâce)',
                    'suspended' => __('Suspendu (non-paiement)'),
                    default => 'Pas encore d’abonnement',
                })
                ->badge()
                ->color(fn (string $state, Consulate $r) => match ($state) {
                    'active' => 'success',
                    'expiring', 'grace' => 'warning',
                    'suspended' => 'danger',
                    // Consulat en ligne sans aucun abonnement enregistré : à régulariser.
                    default => $r->is_active ? 'warning' : 'gray',
                })
                ->visible(fn () => auth()->user()?->isSuperAdmin()),
        ])->actions([
            Tables\Actions\EditAction::make(),
            // Renouvellement : ouvre le formulaire de paiement déjà rempli pour la période suivante.
            // Sans abonnement encore : le même bouton enregistre le premier paiement.
            Tables\Actions\Action::make('renew')
                ->label(fn (Consulate $record) => $record->subscription_until ? 'Renouveler' : 'Enregistrer le paiement')
                ->icon('heroicon-o-arrow-path')->color('gray')
                ->url(fn (Consulate $record) => \App\Filament\Admin\Resources\ConsulatePaymentResource::getUrl('create', ['consulate_id' => $record->id])),
            // Activation : après réception du paiement, pour un consulat inactif déjà abonné.
            Tables\Actions\Action::make('activate')->label(__('Activer'))->icon('heroicon-o-check-circle')->color('success')
                ->visible(fn (Consulate $record) => ! $record->is_active && $record->subscription_until !== null)
                ->requiresConfirmation()
                ->modalDescription(__('Le consulat redevient visible sur le site et son personnel peut se connecter.'))
                ->action(function (Consulate $record) {
                    app(\App\Actions\ActivateConsulate::class)($record);
                    \Filament\Notifications\Notification::make()->title(__('Consulat activé'))->success()->send();
                }),
            Tables\Actions\Action::make('site')->label(__('Voir le site'))->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (Consulate $record) => rtrim((string) config('app.frontend_url'), '/').'/'.$record->slug.'/fr', shouldOpenInNewTab: true),
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

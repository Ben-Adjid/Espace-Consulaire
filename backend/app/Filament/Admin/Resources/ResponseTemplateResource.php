<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\HasConsulateField;
use App\Filament\Admin\Resources\ResponseTemplateResource\Pages;
use App\Models\ResponseTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Modèles de réponses du consulat : motifs de refus ou compléments demandés, insérés en un clic
 * lors de l'examen d'une pré-demande. Le consul les gère ; les agents les utilisent.
 */
class ResponseTemplateResource extends Resource
{
    use \App\Filament\Concerns\TranslatedResourceLabels;
    use HasConsulateField;

    protected static ?string $model = ResponseTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static ?string $navigationGroup = 'Paramétrage';

    protected static ?string $navigationLabel = 'Modèles de réponses';

    protected static ?string $modelLabel = 'modèle de réponse';

    protected static ?string $pluralModelLabel = 'modèles de réponses';

    protected static ?int $navigationSort = 7;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isSuperAdmin() || $user?->can('manage_appointments'));
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isSuperAdmin() || $user?->can('manage_content'));
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
            Forms\Components\TextInput::make('title')->label(__('Titre'))->required()->maxLength(120)
                ->helperText(__('Ex. Photo non conforme')),
            Forms\Components\Textarea::make('body')->label(__('Texte envoyé au citoyen'))->required()->rows(4)->maxLength(500),
            Forms\Components\Toggle::make('is_active')->label(__('Proposé lors de l’examen'))->default(true),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label(__('Titre'))->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('body')->label(__('Texte'))->limit(80)->wrap(),
                Tables\Columns\IconColumn::make('is_active')->label(__('Actif'))->boolean(),
                Tables\Columns\TextColumn::make('consulate.slug')->label(__('Consulat'))->badge()
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading(__('Aucun modèle de réponse'))
            ->emptyStateDescription(__('Créez des motifs fréquents (photo non conforme, pièce illisible…) pour les insérer en un clic.'));
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageResponseTemplates::route('/')];
    }
}

<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\HasConsulateField;
use App\Filament\Admin\Resources\AssociationResource\Pages;
use App\Filament\Admin\Resources\AssociationResource\RelationManagers;
use App\Models\Association;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Association des étudiants et stagiaires tchadiens du pays de résidence.
 * Le consulat la reconnaît et tient son registre : bureau (avec mandats) et membres.
 */
class AssociationResource extends Resource
{
    use \App\Filament\Concerns\TranslatedResourceLabels;

    use HasConsulateField;

    protected static ?string $model = Association::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $modelLabel = 'association';

    protected static ?string $pluralModelLabel = 'association des étudiants';

    protected static ?string $navigationLabel = 'Association des étudiants';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identité')->columns(2)->schema([
                static::consulateField(),
                Forms\Components\TextInput::make('name')->label(__('Nom de l’association'))->required()->maxLength(160)->columnSpanFull(),
                Forms\Components\TextInput::make('acronym')->label(__('Sigle'))->maxLength(30),
                Forms\Components\TextInput::make('email')->label(__('Email de contact'))->email()->maxLength(160),
                Forms\Components\TextInput::make('phone')->label(__('Téléphone de contact'))->tel()->maxLength(32),
                Forms\Components\Textarea::make('description')->label(__('Présentation (publique)'))->rows(4)->maxLength(2000)->columnSpanFull(),
            ]),
            Forms\Components\Section::make('Reconnaissance par le consulat')->columns(3)->schema([
                Forms\Components\Toggle::make('is_recognized')->label(__('Reconnue officiellement'))->live(),
                Forms\Components\DatePicker::make('recognized_at')->label(__('Reconnue le'))->visible(fn (Forms\Get $get) => $get('is_recognized')),
                Forms\Components\Toggle::make('is_public')->label(__('Afficher sur le site public'))->default(true)
                    ->helperText(__('Seuls le nom, la présentation, le bureau et les contacts sont publiés. Jamais la liste des membres.')),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label(__('Association'))->searchable()->wrap(),
            Tables\Columns\TextColumn::make('consulate.slug')->label(__('Consulat'))->badge()
                ->visible(fn () => auth()->user()?->isSuperAdmin()),
            Tables\Columns\IconColumn::make('is_recognized')->label(__('Reconnue'))->boolean(),
            Tables\Columns\TextColumn::make('officers_count')->label(__('Bureau'))->counts('officers'),
            Tables\Columns\TextColumn::make('members_count')->label(__('Membres'))->counts('members'),
            Tables\Columns\IconColumn::make('is_public')->label(__('Publique'))->boolean(),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OfficersRelationManager::class,
            RelationManagers\ElectionsRelationManager::class,
            RelationManagers\CandidaturesRelationManager::class,
            RelationManagers\ActivitiesRelationManager::class,
            RelationManagers\ArchivesRelationManager::class,
            RelationManagers\PostConditionsRelationManager::class,
            RelationManagers\CommissionRelationManager::class,
            RelationManagers\MembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssociations::route('/'),
            'create' => Pages\CreateAssociation::route('/create'),
            'edit' => Pages\EditAssociation::route('/{record}/edit'),
        ];
    }
}

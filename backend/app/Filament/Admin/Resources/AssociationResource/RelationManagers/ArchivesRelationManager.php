<?php

namespace App\Filament\Admin\Resources\AssociationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** Archives : personnalités marquantes ayant un lien avec l'association (publiées seulement si cochées). */
class ArchivesRelationManager extends RelationManager
{
    protected static string $relationship = 'archives';

    protected static bool $isLazy = false;

    protected static ?string $title = 'Archives';

    protected static ?string $modelLabel = 'personnalité';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('person_name')->label(__('Nom'))->required()->maxLength(120),
            Forms\Components\TextInput::make('title')->label(__('Fonction ou titre'))->maxLength(160),
            Forms\Components\Textarea::make('summary')->label(__('Présentation'))->rows(4)->maxLength(2000)->columnSpanFull(),
            Forms\Components\Toggle::make('is_public')->label(__('Publier sur le site'))->default(true),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('person_name')->label(__('Nom'))->searchable(),
                Tables\Columns\TextColumn::make('title')->label(__('Fonction')),
                Tables\Columns\IconColumn::make('is_public')->label(__('Publiée'))->boolean(),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()->label(__('Ajouter une personnalité'))])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }
}

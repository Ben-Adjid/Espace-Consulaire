<?php

namespace App\Filament\Admin\Resources\AssociationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** Activités de l'association. Publiées sur le site si la case est cochée. */
class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static bool $isLazy = false;

    protected static ?string $title = 'Activités';

    protected static ?string $modelLabel = 'activité';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->label(__('Titre'))->required()->maxLength(160)->columnSpanFull(),
            Forms\Components\DatePicker::make('starts_on')->label(__('Date'))->required(),
            Forms\Components\TextInput::make('location')->label(__('Lieu'))->maxLength(160),
            Forms\Components\Textarea::make('description')->label(__('Description'))->rows(3)->maxLength(2000)->columnSpanFull(),
            Forms\Components\Toggle::make('is_public')->label(__('Publier sur le site'))->default(true),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_on', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('starts_on')->label(__('Date'))->date('d/m/Y'),
                Tables\Columns\TextColumn::make('title')->label(__('Activité'))->searchable(),
                Tables\Columns\TextColumn::make('location')->label(__('Lieu'))->toggleable(),
                Tables\Columns\IconColumn::make('is_public')->label(__('Publiée'))->boolean(),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()->label(__('Ajouter une activité'))])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }
}

<?php

namespace App\Filament\Admin\Resources\AssociationResource\RelationManagers;

use App\Models\Association;
use App\Models\AssociationOfficer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** Bureau de l'association : rôles et mandats. Publié sur le site si l'association est publique. */
class OfficersRelationManager extends RelationManager
{
    protected static string $relationship = 'officers';

    // Liste courte : chargée immédiatement (pas de chargement différé à l'affichage).
    protected static bool $isLazy = false;

    protected static ?string $title = 'Bureau';

    protected static ?string $modelLabel = 'membre du bureau';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('role')->label(__('Fonction'))->options(Association::ROLES)->required(),
            Forms\Components\TextInput::make('full_name')->label(__('Nom complet'))->required()->maxLength(120),
            Forms\Components\TextInput::make('school')->label(__('Établissement'))->maxLength(160),
            Forms\Components\TextInput::make('email')->label(__('Email'))->email()->maxLength(160),
            Forms\Components\TextInput::make('phone')->label(__('Téléphone'))->tel()->maxLength(32),
            Forms\Components\DatePicker::make('mandate_start')->label(__('Début du mandat')),
            Forms\Components\DatePicker::make('mandate_end')->label(__('Fin du mandat'))->afterOrEqual('mandate_start'),
            Forms\Components\TextInput::make('sort_order')->label(__('Ordre d’affichage'))->numeric()->default(0),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('role')->label(__('Fonction'))->formatStateUsing(fn (string $state) => Association::ROLES[$state] ?? $state)->badge(),
                Tables\Columns\TextColumn::make('full_name')->label(__('Nom')),
                Tables\Columns\TextColumn::make('school')->label(__('Établissement'))->toggleable(),
                Tables\Columns\TextColumn::make('mandate_end')->label(__('Mandat'))
                    ->state(fn (AssociationOfficer $r) => $r->isCurrent() ? 'En cours' : 'Terminé')
                    ->badge()->color(fn (string $state) => $state === 'En cours' ? 'success' : 'gray'),
            ])
            // Liste de citoyens dans la fenêtre : un clic dans la liste ne doit pas la fermer.
            ->headerActions([Tables\Actions\CreateAction::make()->label(__('Ajouter au bureau'))->closeModalByClickingAway(false)])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }
}

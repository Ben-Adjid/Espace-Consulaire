<?php

namespace App\Filament\Admin\Resources\AssociationResource\RelationManagers;

use App\Models\AssociationMember;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** Registre des membres (étudiants, stagiaires). Données personnelles : jamais publiées. */
class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    // Liste courte : chargée immédiatement (pas de chargement différé à l'affichage).
    protected static bool $isLazy = false;

    protected static ?string $title = 'Membres';

    protected static ?string $modelLabel = 'membre';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('full_name')->label(__('Nom complet'))->required()->maxLength(120),
            Forms\Components\Select::make('category')->label(__('Catégorie'))->options(AssociationMember::CATEGORIES)->default('student')->required(),
            Forms\Components\TextInput::make('school')->label(__('Établissement'))->maxLength(160),
            Forms\Components\TextInput::make('field_of_study')->label(__('Filière'))->maxLength(160),
            Forms\Components\TextInput::make('level')->label(__('Niveau (ex. Licence 2)'))->maxLength(60),
            Forms\Components\TextInput::make('email')->label(__('Email'))->email()->maxLength(160),
            Forms\Components\TextInput::make('phone')->label(__('Téléphone'))->tel()->maxLength(32),
            Forms\Components\DatePicker::make('joined_at')->label(__('Inscrit le'))->default(now()),
            Forms\Components\Select::make('status')->label(__('Statut'))->options(AssociationMember::STATUSES)->default('active')->required(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')->label(__('Nom'))->searchable(),
                Tables\Columns\TextColumn::make('category')->label(__('Catégorie'))->formatStateUsing(fn (string $state) => AssociationMember::CATEGORIES[$state] ?? $state)->badge(),
                Tables\Columns\TextColumn::make('school')->label(__('Établissement'))->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('level')->label(__('Niveau'))->toggleable(),
                Tables\Columns\TextColumn::make('status')->label(__('Statut'))->formatStateUsing(fn (string $state) => AssociationMember::STATUSES[$state] ?? $state)->badge()
                    ->color(fn (string $state) => $state === 'active' ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->label(__('Catégorie'))->options(AssociationMember::CATEGORIES),
                Tables\Filters\SelectFilter::make('status')->label(__('Statut'))->options(AssociationMember::STATUSES),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()->label(__('Inscrire un membre'))])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }
}

<?php

namespace App\Filament\Admin\Resources\AssociationResource\RelationManagers;

use App\Models\Association;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Conditions de candidature par fonction, à fixer selon les statuts de l'association.
 * Sans règle enregistrée pour une fonction, aucune condition ne s'applique à elle.
 */
class PostConditionsRelationManager extends RelationManager
{
    protected static string $relationship = 'postRules';

    protected static bool $isLazy = false;

    protected static ?string $title = 'Conditions par fonction';

    protected static ?string $modelLabel = 'condition';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('role')->label(__('Fonction'))->options(Association::ROLES)->required()
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->where('association_id', $this->getOwnerRecord()->id))
                ->validationMessages(['unique' => __('Une condition existe déjà pour cette fonction.')]),
            Forms\Components\TextInput::make('min_membership_months')->label(__('Adhésion minimale (mois)'))
                ->numeric()->minValue(0)->maxValue(120)->default(0)
                ->helperText(__('Nombre de mois d’adhésion active avant de pouvoir être candidat.')),
            Forms\Components\Toggle::make('requires_student')->label(__('Réservé aux étudiants et stagiaires'))->default(false),
            Forms\Components\Toggle::make('requires_document')->label(__('Pièce justificative vérifiée par le consulat'))->default(false)
                ->helperText(__('La candidature ne peut être validée qu’après vérification de la pièce.')),
            Forms\Components\TextInput::make('max_consecutive_terms')->label(__('Nombre maximal de mandats déjà exercés'))
                ->numeric()->minValue(0)->maxValue(20)
                ->helperText(__('Laisser vide : aucune limite. Compte les mandats terminés dans cette fonction.')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('role')->label(__('Fonction'))
                    ->formatStateUsing(fn (string $state) => Association::ROLES[$state] ?? $state)->badge(),
                Tables\Columns\TextColumn::make('min_membership_months')->label(__('Adhésion (mois)')),
                Tables\Columns\IconColumn::make('requires_student')->label(__('Étudiant'))->boolean(),
                Tables\Columns\IconColumn::make('requires_document')->label(__('Pièce'))->boolean(),
                Tables\Columns\TextColumn::make('max_consecutive_terms')->label(__('Mandats max.'))->placeholder('—'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label(__('Ajouter une condition')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}

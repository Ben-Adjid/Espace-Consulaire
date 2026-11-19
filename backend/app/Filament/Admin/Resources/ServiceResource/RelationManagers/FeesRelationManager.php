<?php

namespace App\Filament\Admin\Resources\ServiceResource\RelationManagers;

use App\Models\ServiceFee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Historique des tarifs d'un service. Un tarif est ajouté avec sa date d'effet et sa base légale ;
 * il ne peut ni être modifié ni supprimé. Le site affiche le tarif en vigueur à la date du jour.
 */
class FeesRelationManager extends RelationManager
{
    protected static string $relationship = 'fees';

    protected static ?string $title = 'Historique des tarifs';

    protected static ?string $modelLabel = 'tarif';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('amount')->label(__('Montant'))->numeric()->minValue(0)->required(),
            Forms\Components\TextInput::make('currency')->label(__('Devise'))->default('XOF')->required()->maxLength(8),
            Forms\Components\DatePicker::make('effective_from')->label(__('En vigueur à partir du'))->required()
                ->helperText(__('Un tarif futur n’apparaît sur le site qu’à cette date.')),
            Forms\Components\TextInput::make('legal_reference')->label(__('Base légale'))->required()->maxLength(200)
                ->placeholder(__('Ex. : Arrêté n° … du … portant tarifs consulaires'))
                ->helperText(__('Référence du texte qui fixe ce montant. Obligatoire.')),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('effective_from', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('effective_from')->label(__('En vigueur depuis'))->date('d/m/Y'),
                Tables\Columns\TextColumn::make('amount')->label(__('Montant'))->formatStateUsing(fn (ServiceFee $r) => $r->label()),
                Tables\Columns\TextColumn::make('legal_reference')->label(__('Base légale'))->wrap(),
                Tables\Columns\TextColumn::make('recorded_by')->label(__('Enregistré par'))
                    ->formatStateUsing(fn ($state) => \App\Models\User::find($state)?->name ?? '—')->toggleable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label(__('Ajouter un tarif'))
                    ->mutateFormDataUsing(fn (array $data) => $data + ['recorded_by' => auth()->id()]),
            ])
            ->actions([])
            ->bulkActions([]);
    }
}

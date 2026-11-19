<?php

namespace App\Filament\Admin\Resources\AssociationResource\RelationManagers;

use App\Actions\CommissionActions;
use App\Models\AssociationCommissionMember;
use App\Models\Citizen;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

/**
 * Commission électorale : trois citoyens hors personnel. Elle atteste les résultats publiés ; elle ne les modifie pas.
 */
class CommissionRelationManager extends RelationManager
{
    protected static string $relationship = 'commissionMembers';

    protected static bool $isLazy = false;

    protected static ?string $title = 'Commission électorale';

    protected static ?string $modelLabel = 'membre de la commission';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('citizen.full_name')->label(__('Membre'))
                    ->description(fn (AssociationCommissionMember $r) => $r->citizen?->email),
                Tables\Columns\TextColumn::make('created_at')->label(__('Nommé le'))->dateTime('d/m/Y'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('add')->label(__('Ajouter un membre'))->icon('heroicon-o-user-plus')
                    ->visible(fn () => $this->getOwnerRecord()->commissionMembers()->count() < AssociationCommissionMember::MAX_MEMBERS)
                    ->form([
                        Forms\Components\Select::make('citizen_id')->label(__('Citoyen'))->searchable()->required()
                            ->options(fn () => Citizen::query()
                                ->where('consulate_id', $this->getOwnerRecord()->consulate_id)
                                ->where('status', Citizen::STATUS_ACTIVE)
                                ->orderBy('full_name')
                                ->get()
                                ->mapWithKeys(fn ($c) => [$c->id => $c->full_name.' — '.$c->email])),
                    ])
                    ->action(function (array $data) {
                        try {
                            CommissionActions::add($this->getOwnerRecord(), Citizen::findOrFail($data['citizen_id']));
                            Notification::make()->title(__('Membre ajouté à la commission'))->success()->send();
                        } catch (ValidationException $e) {
                            Notification::make()->title(collect($e->errors())->flatten()->first())->danger()->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()->label(__('Retirer')),
            ]);
    }
}

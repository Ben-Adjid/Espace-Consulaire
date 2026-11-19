<?php

namespace App\Filament\Admin\Resources\AssociationResource\RelationManagers;

use App\Actions\CloseAssociationBoard;
use App\Actions\CreateOnlineElection;
use App\Actions\RecordElection;
use App\Actions\TallyElection;
use App\Models\Association;
use App\Models\AssociationElection;
use App\Models\Citizen;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Élections annuelles.
 * - Hors ligne : on saisit le nouveau bureau (il remplace le précédent).
 * - En ligne : on fixe les dates ; le dépouillement, le second tour et la proclamation sont automatiques.
 *   Le consulat ne voit aucun total pendant le vote, et ne peut modifier ni les votes ni les résultats.
 */
class ElectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'elections';

    protected static bool $isLazy = false;

    protected static ?string $title = 'Élections';

    protected static ?string $modelLabel = 'élection';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('year')->label(__('Année'))->numeric()->required()
                ->minValue(2000)->maxValue(2100)->default(now()->year),
            Forms\Components\DatePicker::make('held_on')->label(__('Date de l’élection'))
                ->required(fn (Get $get) => blank($get('voting_opens_at')))
                ->helperText(__('Pour un vote en ligne, la date est celle de la fermeture du scrutin.')),
            Forms\Components\DateTimePicker::make('voting_opens_at')->label(__('Ouverture du vote en ligne'))
                ->helperText(__('Laisser vide pour une élection hors ligne. Avant cette date : période de candidature.')),
            Forms\Components\DateTimePicker::make('voting_closes_at')->label(__('Fermeture du vote en ligne'))
                ->afterOrEqual('voting_opens_at')
                ->required(fn (Get $get) => filled($get('voting_opens_at'))),
            Forms\Components\TextInput::make('second_round_hours')->label(__('Durée du second tour (heures)'))
                ->numeric()->minValue(24)->maxValue(336)->default(72)
                ->helperText(__('Ouvert automatiquement si aucun candidat n’a la majorité absolue au premier tour.')),
            Forms\Components\Textarea::make('notes')->label(__('Notes (interne)'))->rows(2)->maxLength(2000)->columnSpanFull(),
            Forms\Components\Repeater::make('officers')->label(__('Nouveau bureau (élections hors ligne)'))
                ->required(fn (Get $get) => blank($get('voting_opens_at')))
                ->minItems(fn (Get $get) => blank($get('voting_opens_at')) ? 1 : 0)
                ->columnSpanFull()->columns(3)
                ->visible(fn (Get $get) => blank($get('voting_opens_at')))
                ->schema([
                    Forms\Components\Select::make('role')->label(__('Fonction'))->options(Association::ROLES)->required(),
                    Forms\Components\TextInput::make('full_name')->label(__('Nom complet'))->required()->maxLength(120),
                    Forms\Components\TextInput::make('school')->label(__('Établissement'))->maxLength(160),
                    // Compte citoyen du titulaire : son espace affiche alors sa fonction, et elle disparaît à la fin du mandat.
                    Forms\Components\Select::make('citizen_id')->label(__('Compte citoyen (optionnel)'))
                        ->searchable()->preload()
                        ->options(fn () => Citizen::query()
                            ->where('consulate_id', $this->getOwnerRecord()->consulate_id)
                            ->where('status', Citizen::STATUS_ACTIVE)
                            ->orderBy('full_name')
                            ->get()
                            ->mapWithKeys(fn ($c) => [$c->id => $c->full_name.' — '.$c->email]))
                        ->helperText(__('Le titulaire reçoit un email et voit sa fonction dans son espace.'))
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('year', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('year')->label(__('Année'))->badge(),
                Tables\Columns\TextColumn::make('phase')->label(__('Phase'))->badge()
                    ->getStateUsing(fn (AssociationElection $r) => match ($r->phase()) {
                        'candidacy' => __('Candidatures'),
                        'voting' => __('Vote en cours'),
                        'closed' => __('Dépouillement en cours'),
                        'proclaimed' => __('Proclamé'),
                        default => __('Hors ligne'),
                    })
                    ->color(fn (AssociationElection $r) => match ($r->phase()) {
                        'voting' => 'success',
                        'closed' => 'warning',
                        'candidacy' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('held_on')->label(__('Date'))->date('d/m/Y'),
                Tables\Columns\TextColumn::make('voting_closes_at')->label(__('Fermeture du vote'))->dateTime('d/m/Y H:i')->placeholder('—'),
                Tables\Columns\TextColumn::make('officers_count')->label(__('Membres élus'))->counts('officers'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('Enregistrer une élection'))
                    ->modalHeading(__('Enregistrer une élection'))
                    ->using(function (array $data, RelationManager $livewire) {
                        $association = $livewire->getOwnerRecord();

                        if (filled($data['voting_opens_at'] ?? null)) {
                            return app(CreateOnlineElection::class)(
                                $association,
                                (int) $data['year'],
                                $data['voting_opens_at'],
                                $data['voting_closes_at'],
                                $data['notes'] ?? null,
                                (int) ($data['second_round_hours'] ?? 72),
                            );
                        }

                        return app(RecordElection::class)(
                            $association,
                            (int) $data['year'],
                            $data['held_on'],
                            $data['officers'],
                            $data['notes'] ?? null,
                        );
                    }),
                // Fin de mandat sans nouvelle élection : archive le bureau en cours, les titulaires redeviennent citoyens simples.
                Tables\Actions\Action::make('closeBoard')
                    ->label(__('Archiver le bureau en cours'))
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription(__('Les mandats en cours se terminent aujourd’hui. Les titulaires reçoivent un email et redeviennent citoyens simples. Le bureau reste consultable dans l’historique.'))
                    ->action(function (RelationManager $livewire) {
                        $count = app(CloseAssociationBoard::class)($livewire->getOwnerRecord());
                        Notification::make()
                            ->title($count.' mandat(s) archivé(s)')->success()->send();
                    }),
            ])
            ->actions([
                // Résultats publiés par le système, par tour. Rien n'est affiché tant que le scrutin n'est pas traité.
                Tables\Actions\Action::make('results')
                    ->label(__('Résultats'))
                    ->icon('heroicon-o-chart-bar')
                    ->color('gray')
                    ->visible(fn (AssociationElection $r) => $r->isOnline())
                    ->modalHeading(fn (AssociationElection $r) => __('Résultats :year', ['year' => $r->year]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('Fermer'))
                    ->modalContent(fn (AssociationElection $r) => view('filament.admin.association-tally', [
                        'rounds' => app(TallyElection::class)($r),
                        'phase' => $r->phase(),
                    ])),
            ]);
    }
}

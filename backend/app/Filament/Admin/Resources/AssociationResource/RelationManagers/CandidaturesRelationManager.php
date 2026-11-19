<?php

namespace App\Filament\Admin\Resources\AssociationResource\RelationManagers;

use App\Actions\ReviewCandidacy;
use App\Models\Association;
use App\Models\AssociationCandidate;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

/** Candidatures aux élections en ligne. Validation ou refus pendant la période de candidature. */
class CandidaturesRelationManager extends RelationManager
{
    protected static string $relationship = 'candidates';

    protected static bool $isLazy = false;

    protected static ?string $title = 'Candidatures';

    protected static ?string $modelLabel = 'candidature';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('election.year')->label(__('Élection'))->badge(),
                Tables\Columns\TextColumn::make('citizen.full_name')->label(__('Candidat'))
                    ->description(fn (AssociationCandidate $r) => $r->citizen?->email),
                Tables\Columns\TextColumn::make('role')->label(__('Fonction'))
                    ->formatStateUsing(fn (string $state) => Association::ROLES[$state] ?? $state),
                Tables\Columns\TextColumn::make('status')->label(__('Statut'))->badge()
                    ->formatStateUsing(fn (string $state) => __(AssociationCandidate::STATUSES[$state] ?? $state))
                    ->color(fn (string $state) => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label(__('Déposée le'))->dateTime('d/m/Y H:i'),
            ])
            ->actions([
                // Pièce justificative exigée par la fonction : le consulat confirme l'avoir vérifiée avant de valider.
                Tables\Actions\Action::make('checkDocument')->label(__('Pièce vérifiée'))->icon('heroicon-o-document-check')->color('info')
                    ->visible(fn (AssociationCandidate $r) => $r->election->phase() === 'candidacy'
                        && $r->document_checked_at === null
                        && \App\Actions\PostConditions::requiresDocument($r->election->association, $r->role))
                    ->requiresConfirmation()
                    ->modalDescription(__('Confirmez avoir vérifié la pièce justificative de ce candidat pour cette fonction.'))
                    ->action(function (AssociationCandidate $r) {
                        try {
                            app(\App\Actions\CheckCandidateDocument::class)($r, auth()->user());
                            Notification::make()->title(__('Pièce marquée comme vérifiée'))->success()->send();
                        } catch (ValidationException $e) {
                            Notification::make()->title(collect($e->errors())->flatten()->first())->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('approve')->label(__('Valider'))->icon('heroicon-o-check')->color('success')
                    ->visible(fn (AssociationCandidate $r) => $r->status !== 'approved' && $r->election->phase() === 'candidacy')
                    ->requiresConfirmation()
                    ->action(fn (AssociationCandidate $r) => $this->review($r, 'approved')),
                Tables\Actions\Action::make('reject')->label(__('Refuser'))->icon('heroicon-o-x-mark')->color('danger')
                    ->visible(fn (AssociationCandidate $r) => $r->status !== 'rejected' && $r->election->phase() === 'candidacy')
                    ->requiresConfirmation()
                    ->action(fn (AssociationCandidate $r) => $this->review($r, 'rejected')),
            ])
            ->bulkActions([]);
    }

    private function review(AssociationCandidate $candidate, string $decision): void
    {
        try {
            app(ReviewCandidacy::class)($candidate, $decision);
            Notification::make()->title($decision === 'approved' ? __('Candidature validée') : __('Candidature refusée'))->success()->send();
        } catch (ValidationException $e) {
            Notification::make()->title(collect($e->errors())->flatten()->first())->danger()->send();
        }
    }
}

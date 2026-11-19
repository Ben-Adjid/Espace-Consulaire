<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CitizenResource\Pages;
use App\Mail\CitizenDecision;
use App\Models\Citizen;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * File de validation des comptes citoyens : le consulat vérifie la résidence, puis active ou refuse le compte.
 */
class CitizenResource extends Resource
{
    use \App\Filament\Concerns\TranslatedResourceLabels;

    protected static ?string $model = Citizen::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Dossiers et citoyens';

    protected static ?string $modelLabel = 'compte citoyen';

    protected static ?string $pluralModelLabel = 'comptes citoyens';

    protected static ?string $navigationLabel = 'Comptes citoyens';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', Citizen::STATUS_PENDING)->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('full_name')->label(__('Nom'))->searchable(),
                Tables\Columns\TextColumn::make('email')->label(__('Email'))->searchable(),
                Tables\Columns\TextColumn::make('phone')->label(__('Téléphone')),
                Tables\Columns\TextColumn::make('residence_country')->label(__('Pays de résidence'))->badge(),
                Tables\Columns\IconColumn::make('phone_mismatch')->label(__('Téléphone d’un autre pays'))->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')->falseIcon('heroicon-o-check')
                    ->trueColor('warning')->falseColor('success')->tooltip(__('Le numéro indique un autre pays que la résidence déclarée : à vérifier.')),
                Tables\Columns\TextColumn::make('residence_proof')->label(__('Justificatif annoncé'))->limit(60)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')->label(__('Statut'))->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        Citizen::STATUS_ACTIVE => 'Actif',
                        Citizen::STATUS_REJECTED => 'Refusé',
                        default => 'En attente',
                    })
                    ->color(fn (string $state) => match ($state) {
                        Citizen::STATUS_ACTIVE => 'success',
                        Citizen::STATUS_REJECTED => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label(__('Demande le'))->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label(__('Statut'))->options([
                    Citizen::STATUS_PENDING => 'En attente',
                    Citizen::STATUS_ACTIVE => 'Actif',
                    Citizen::STATUS_REJECTED => 'Refusé',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label(__('Valider la résidence'))
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Citizen $r) => $r->status !== Citizen::STATUS_ACTIVE)
                    ->requiresConfirmation()
                    ->modalDescription(fn (Citizen $r) => $r->phone_mismatch
                        ? 'ATTENTION : le numéro de téléphone indique un autre pays que la résidence déclarée. Vérifiez le justificatif avant de valider. Le citoyen recevra un email.'
                        : 'Confirmez que le justificatif de résidence a été vérifié. Le citoyen recevra un email.')
                    ->action(function (Citizen $r) {
                        $r->update([
                            'status' => Citizen::STATUS_ACTIVE,
                            'validated_at' => now(),
                            'validated_by' => auth()->id(),
                            'rejection_reason' => null,
                        ]);
                        self::notify($r, 'active');
                    }),
                Tables\Actions\Action::make('reject')
                    ->label(__('Refuser'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Citizen $r) => $r->status !== Citizen::STATUS_REJECTED)
                    ->form([
                        Forms\Components\Textarea::make('reason')->label(__('Motif (communiqué au citoyen)'))->required()->maxLength(500),
                    ])
                    ->action(function (Citizen $r, array $data) {
                        $r->update([
                            'status' => Citizen::STATUS_REJECTED,
                            'rejection_reason' => $data['reason'],
                            'validated_by' => auth()->id(),
                            'validated_at' => now(),
                        ]);
                        self::notify($r, 'rejected');
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCitizens::route('/'),
        ];
    }

    private static function notify(Citizen $citizen, string $kind): void
    {
        try {
            Mail::to($citizen->email)->send(new CitizenDecision($citizen, $kind));
        } catch (\Throwable $e) {
            Log::warning('Décision de compte non envoyée', ['citizen' => $citizen->id, 'error' => $e->getMessage()]);
        }
    }
}

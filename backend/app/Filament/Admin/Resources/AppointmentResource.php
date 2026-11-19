<?php

namespace App\Filament\Admin\Resources;

use App\Enums\AppointmentStatus;
use App\Filament\Admin\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AppointmentResource extends Resource
{
    use \App\Filament\Concerns\TranslatedResourceLabels;

    protected static ?string $model = Appointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Rendez-vous';

    protected static ?string $navigationGroup = 'Accueil du public';

    protected static ?string $modelLabel = 'rendez-vous';

    protected static ?string $pluralModelLabel = 'rendez-vous';

    protected static ?int $navigationSort = 2;

    /** Les rendez-vous sont créés par le public ; l'admin gère leur statut. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Appointment::query()->where('status', AppointmentStatus::Pending->value)->count();

        return $count ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Demande')->columns(2)->schema([
                Forms\Components\TextInput::make('reference')->label(__('Référence'))->disabled(),
                Forms\Components\Select::make('status')->label(__('Statut'))
                    ->options(AppointmentStatus::class)->required()->native(false),
                Forms\Components\DatePicker::make('appointment_date')->label(__('Date'))->disabled(),
                Forms\Components\TextInput::make('start_time')->label(__('Heure'))->disabled()
                    ->formatStateUsing(fn ($state) => substr((string) $state, 0, 5)),
                Forms\Components\TextInput::make('full_name')->label(__('Demandeur'))->disabled(),
                Forms\Components\TextInput::make('phone')->label(__('Téléphone'))->disabled(),
                Forms\Components\TextInput::make('email')->label(__('Email'))->disabled(),
                Forms\Components\Textarea::make('notes')->label(__('Notes / message'))->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['service']))
            ->defaultSort('appointment_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('reference')->label(__('Référence'))->searchable()->copyable(),
                Tables\Columns\TextColumn::make('appointment_date')->label(__('Date'))->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('start_time')->label(__('Heure'))
                    ->formatStateUsing(fn ($state) => substr((string) $state, 0, 5)),
                Tables\Columns\TextColumn::make('service.name')->label(__('Service')),
                Tables\Columns\TextColumn::make('full_name')->label(__('Demandeur'))->searchable(),
                Tables\Columns\TextColumn::make('phone')->label(__('Téléphone')),
                Tables\Columns\TextColumn::make('residence_country')->label(__('Pays de résidence'))->toggleable(),
                Tables\Columns\TextColumn::make('consulate.slug')->label(__('Consulat'))->badge()
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
                Tables\Columns\TextColumn::make('status')->label(__('Statut'))->badge(),
                // Dossier en ligne de la démarche : un rendez-vous sans dossier validé se voit au premier coup d'œil.
                Tables\Columns\TextColumn::make('preRequest.status')->label(__('Dossier en ligne'))->badge()
                    ->formatStateUsing(fn (?string $state) => $state ? __(\App\Models\PreRequest::STATUSES[$state] ?? $state) : __('Aucun'))
                    ->color(fn (?string $state) => match ($state) {
                        'validated' => 'success',
                        'rejected' => 'danger',
                        'returned', 'submitted' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('checked_in_at')->label(__('Arrivée'))->dateTime('H:i')->placeholder(__('—'))->toggleable(),
                Tables\Columns\TextColumn::make('case_status')->label(__('Dossier'))
                    ->formatStateUsing(fn (?\App\Enums\CaseStatus $state) => $state?->label())
                    ->placeholder(__('—'))->badge()
                    ->color(fn (?\App\Enums\CaseStatus $state) => match ($state) {
                        \App\Enums\CaseStatus::Ready => 'success',
                        \App\Enums\CaseStatus::Rejected => 'danger',
                        \App\Enums\CaseStatus::Collected => 'gray',
                        default => 'info',
                    })->toggleable(),
                Tables\Columns\TextColumn::make('on_behalf_name')->label(__('Pour le compte de'))->placeholder(__('—'))->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('queue_number')->label(__('Ticket'))
                    ->formatStateUsing(fn (?int $state) => \App\Actions\CheckInAppointment::label($state))
                    ->placeholder(__('—'))->badge()->color('info')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label(__('Statut'))->options(AppointmentStatus::class),
                Tables\Filters\Filter::make('today')->label(__("Aujourd'hui"))
                    ->query(fn (Builder $q) => $q->whereDate('appointment_date', today())),
                // File d'attente : arrivés aujourd'hui, pas encore terminés, dans l'ordre des tickets.
                Tables\Filters\Filter::make('queue')->label(__("File d'attente du jour"))
                    ->query(fn (Builder $q) => $q->whereDate('appointment_date', today())
                        ->whereNotNull('checked_in_at')
                        ->whereIn('status', [AppointmentStatus::Pending, AppointmentStatus::Confirmed])
                        ->reorder('queue_number')),
                // Absences : rendez-vous passés, jamais présentés, ni annulés ni terminés.
                Tables\Filters\Filter::make('no_show')->label(__('Absences'))
                    ->query(fn (Builder $q) => $q->whereDate('appointment_date', '<', today())
                        ->whereNull('checked_in_at')
                        ->whereIn('status', [AppointmentStatus::Pending, AppointmentStatus::Confirmed])),
                Tables\Filters\Filter::make('upcoming')->label(__('À venir'))
                    ->query(fn (Builder $q) => $q->whereDate('appointment_date', '>=', today())),
            ])
            ->actions([
                // File d'attente : le citoyen s'est présenté au guichet le jour de son rendez-vous.
                Tables\Actions\Action::make('checkin')->label(__('Arrivé'))->icon('heroicon-o-user-group')->color('info')
                    ->visible(fn (Appointment $r) => $r->checked_in_at === null
                        && in_array($r->status, [AppointmentStatus::Pending, AppointmentStatus::Confirmed], true)
                        && $r->appointment_date->isToday())
                    ->action(fn (Appointment $r) => app(\App\Actions\CheckInAppointment::class)->execute($r)),
                // Notes internes : visibles du personnel uniquement, jamais du citoyen.
                Tables\Actions\Action::make('notes')->label(__('Notes internes'))->icon('heroicon-o-pencil-square')->color('gray')
                    ->modalHeading(fn (Appointment $r) => 'Notes internes · '.$r->reference)
                    ->modalDescription(__('Visibles uniquement par le personnel du consulat. Le citoyen ne les voit jamais.'))
                    ->form(fn (Appointment $r) => [
                        Forms\Components\Placeholder::make('history')->label(__('Notes précédentes'))
                            ->content(function () use ($r) {
                                $notes = \App\Models\AppointmentNote::withoutGlobalScopes()
                                    ->where('appointment_id', $r->id)->with('author')->latest()->get();

                                return $notes->isEmpty() ? 'Aucune note.' : $notes->map(fn ($n) =>
                                    '['.$n->created_at->format('d/m H:i').'] '.($n->author?->name ?? 'Personnel').' : '.$n->body)->implode("\n");
                            }),
                        Forms\Components\Textarea::make('body')->label(__('Nouvelle note'))->required()->maxLength(1000),
                    ])
                    ->action(function (Appointment $r, array $data) {
                        \App\Models\AppointmentNote::withoutGlobalScopes()->create([
                            'appointment_id' => $r->id,
                            'author_id' => auth()->id(),
                            'body' => $data['body'],
                        ]);
                    }),
                // Suivi du dossier : étape visible par le citoyen, avec un message facultatif.
                Tables\Actions\Action::make('case')->label(__('Dossier'))->icon('heroicon-o-folder-open')->color('gray')
                    ->visible(fn (Appointment $r) => $r->status !== AppointmentStatus::Cancelled)
                    ->fillForm(fn (Appointment $r) => ['status' => $r->case_status?->value, 'note' => $r->case_note])
                    ->form([
                        \Filament\Forms\Components\Select::make('status')->label(__('Étape du dossier'))
                            ->options(\App\Enums\CaseStatus::options())->required(),
                        \Filament\Forms\Components\Textarea::make('note')->label(__('Message au citoyen (facultatif)'))->maxLength(500),
                    ])
                    ->action(fn (Appointment $r, array $data) => app(\App\Actions\UpdateCaseStatus::class)
                        ->execute($r, \App\Enums\CaseStatus::from($data['status']), $data['note'] ?? null)),
                // Encaissement des frais au guichet : reçu numéroté rattaché au rendez-vous.
                Tables\Actions\Action::make('receipt')->label(__('Encaisser'))->icon('heroicon-o-banknotes')->color('gray')
                    ->visible(fn (Appointment $r) => \App\Filament\Admin\Resources\ConsularReceiptResource::canCreate()
                        && $r->status !== AppointmentStatus::Cancelled)
                    ->form([
                        \Filament\Forms\Components\TextInput::make('amount')->label(__('Montant'))->numeric()->minValue(0)->required(),
                        \Filament\Forms\Components\TextInput::make('currency')->label(__('Devise'))->default('XOF')->required()->maxLength(8),
                        \Filament\Forms\Components\Select::make('method')->label(__('Mode de paiement'))
                            ->options(\App\Models\ConsularReceipt::METHODS)->required(),
                        \Filament\Forms\Components\TextInput::make('reference')->label(__('Référence'))->maxLength(80),
                    ])
                    ->action(function (Appointment $r, array $data) {
                        try {
                            $receipt = \App\Models\ConsularReceipt::create([
                                'consulate_id' => $r->consulate_id,
                                'appointment_id' => $r->id,
                                'service_id' => $r->service_id,
                                'payer_name' => $r->full_name,
                                'amount' => $data['amount'],
                                'currency' => $data['currency'],
                                'method' => $data['method'],
                                'reference' => $data['reference'] ?? null,
                                'received_by' => auth()->id(),
                            ]);
                        } catch (\LogicException $e) {
                            // Anti-doublon : déjà encaissé. Rien n'est enregistré.
                            \Filament\Notifications\Notification::make()->danger()->title($e->getMessage())->send();

                            return;
                        }
                        \Filament\Notifications\Notification::make()->success()
                            ->title('Reçu '.$receipt->receipt_number.' enregistré')->send();
                    }),
                Tables\Actions\Action::make('confirm')->label(__('Confirmer'))->icon('heroicon-o-check')->color('success')
                    ->visible(fn (Appointment $r) => $r->status === AppointmentStatus::Pending)
                    ->action(function (Appointment $r) {
                        $r->update(['status' => AppointmentStatus::Confirmed, 'confirmed_at' => now()]);
                        \Filament\Notifications\Notification::make()->success()->title('Rendez-vous '.$r->reference.' confirmé')->send();
                    }),
                Tables\Actions\Action::make('complete')->label(__('Terminer'))->icon('heroicon-o-flag')->color('gray')
                    ->visible(fn (Appointment $r) => $r->status === AppointmentStatus::Confirmed)
                    ->requiresConfirmation()
                    ->modalHeading(fn (Appointment $r) => 'Terminer le rendez-vous '.$r->reference.' ?')
                    ->modalDescription(fn (Appointment $r) => $r->full_name.' · '.$r->appointment_date->format('d/m/Y').' '.$r->start_time.'. Le rendez-vous sera clos.')
                    ->action(function (Appointment $r) {
                        $r->update(['status' => AppointmentStatus::Completed]);
                        \Filament\Notifications\Notification::make()->success()->title('Rendez-vous '.$r->reference.' terminé')->send();
                    }),
                Tables\Actions\Action::make('cancel')->label(__('Annuler'))->icon('heroicon-o-x-mark')->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Appointment $r) => 'Annuler le rendez-vous '.$r->reference.' ?')
                    ->modalDescription(fn (Appointment $r) => $r->full_name.' · '.$r->appointment_date->format('d/m/Y').' '.$r->start_time.'. Le créneau sera libéré.')
                    ->modalSubmitActionLabel(__('Oui, annuler'))
                    ->visible(fn (Appointment $r) => in_array($r->status, [AppointmentStatus::Pending, AppointmentStatus::Confirmed], true))
                    ->action(function (Appointment $r) {
                        $r->update(['status' => AppointmentStatus::Cancelled]);
                        \Filament\Notifications\Notification::make()->success()->title('Rendez-vous '.$r->reference.' annulé')->send();
                    }),
                Tables\Actions\EditAction::make()->label(__('Détail')),
            ])
            ->bulkActions([
                // Seuls les rendez-vous en attente sont confirmés : un rendez-vous annulé ou terminé ne repasse jamais en « confirmé ».
                Tables\Actions\BulkAction::make('confirm')->label(__('Confirmer la sélection'))->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->modalDescription(__('Seuls les rendez-vous en attente de la sélection seront confirmés.'))
                    ->action(function ($records) {
                        $pending = $records->filter(fn (Appointment $a) => $a->status === AppointmentStatus::Pending);
                        $pending->each->update(['status' => AppointmentStatus::Confirmed, 'confirmed_at' => now()]);
                        $skipped = $records->count() - $pending->count();
                        \Filament\Notifications\Notification::make()->success()
                            ->title($pending->count().' rendez-vous confirmé(s)')
                            ->body($skipped ? $skipped.' ignoré(s) : ils n’étaient pas en attente.' : null)->send();
                    })
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}

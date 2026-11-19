<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PreRequestResource\Pages;
use App\Models\PreRequest;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Pré-demandes en ligne : le personnel vérifie les pièces, puis valide, renvoie pour complément ou refuse. */
class PreRequestResource extends Resource
{
    use \App\Filament\Concerns\TranslatedResourceLabels;

    protected static ?string $model = PreRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Dossiers et citoyens';

    protected static ?string $modelLabel = 'pré-demande';

    protected static ?string $pluralModelLabel = 'pré-demandes';

    protected static ?string $navigationLabel = 'Pré-demandes';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isSuperAdmin() || $user?->can('manage_appointments'));
    }

    public static function getEloquentQuery(): Builder
    {
        // Dossiers urgents en tête de liste ; ensuite les plus anciens (premier arrivé, premier traité).
        return parent::getEloquentQuery()->where('status', '!=', 'draft')
            ->with(['service', 'citizen', 'documents', 'assignee'])
            ->orderByDesc('is_urgent')->orderBy('submitted_at');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', 'submitted')->count();

        return $count ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        $canManageTeam = fn () => (bool) (auth()->user()?->isSuperAdmin() || auth()->user()?->can('manage_users'));

        return $table
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                Tables\Columns\IconColumn::make('is_urgent')->label(__('Urgent'))->boolean()->trueIcon('heroicon-s-bolt')->falseIcon('heroicon-o-minus')->trueColor('danger'),
                Tables\Columns\TextColumn::make('id')->label(__('N°'))->prefix('#')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('submitted_at')->label(__('Soumise le'))->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('citizen.full_name')->label(__('Citoyen'))
                    ->description(fn (PreRequest $r) => $r->citizen?->email)
                    // Recherche par nom, e-mail ou téléphone du citoyen.
                    ->searchable(['full_name', 'email', 'phone']),
                Tables\Columns\TextColumn::make('service.name')->label(__('Service'))->searchable(),
                Tables\Columns\TextColumn::make('documents_count')->label(__('Pièces'))->counts('documents'),
                Tables\Columns\TextColumn::make('status')->label(__('Statut'))->badge()
                    ->formatStateUsing(fn (string $state) => __(PreRequest::STATUSES[$state] ?? $state))
                    ->color(fn (string $state) => match ($state) {
                        'submitted' => 'warning', 'validated' => 'success', 'rejected' => 'danger', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('assignee.name')->label(__('Attribué à'))->placeholder(__('Non attribué')),
                // Échéance = soumission + délai cible du service ; en rouge si dépassée.
                Tables\Columns\TextColumn::make('due')->label(__('Échéance'))
                    ->state(fn (PreRequest $r) => $r->dueAt()?->format('d/m/Y'))
                    ->placeholder('—')
                    ->badge()
                    ->color(fn (PreRequest $r) => $r->isOverdue() ? 'danger' : 'gray')
                    ->icon(fn (PreRequest $r) => $r->isOverdue() ? 'heroicon-m-exclamation-triangle' : null)
                    ->tooltip(fn (PreRequest $r) => $r->isOverdue() ? __('En retard') : null),
                Tables\Columns\TextColumn::make('consulate.slug')->label(__('Consulat'))->badge()
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label(__('Statut'))
                    ->options(collect(PreRequest::STATUSES)->except('draft')->map(fn ($l) => __($l))->all()),
                Tables\Filters\Filter::make('mine')->label(__('Mes dossiers'))->toggle()
                    ->query(fn (Builder $q) => $q->where('assigned_to', auth()->id())),
                Tables\Filters\Filter::make('unassigned')->label(__('Non attribués'))->toggle()
                    ->query(fn (Builder $q) => $q->where('status', 'submitted')->whereNull('assigned_to')),
                Tables\Filters\Filter::make('overdue')->label(__('En retard'))->toggle()
                    ->query(fn (Builder $q) => $q->whereIn('id', PreRequest::overdueIds())),
                Tables\Filters\SelectFilter::make('service_id')->label(__('Service'))
                    ->options(fn () => \App\Models\Service::query()->get()->mapWithKeys(fn ($s) => [$s->id => $s->name])->all()),
                Tables\Filters\Filter::make('period')->label(__('Période de soumission'))
                    ->form([
                        Forms\Components\DatePicker::make('from')->label(__('Du')),
                        Forms\Components\DatePicker::make('until')->label(__('Au')),
                    ])
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('submitted_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('submitted_at', '<=', $d))),
            ])
            ->actions([
                // Cas particulier : le consulat le signale ; il définit lui-même les pièces à fournir pour ce cas.
                Tables\Actions\Action::make('specialCase')->label(__('Cas particulier'))->icon('heroicon-o-flag')->color('warning')
                    ->visible(fn () => auth()->user()?->isSuperAdmin() || auth()->user()?->can('decide_requests'))
                    ->form([
                        Forms\Components\Select::make('special_case')->label(__('Situation'))
                            ->options(collect(PreRequest::SPECIAL_CASES)->map(fn ($l) => __($l))->all())
                            ->placeholder(__('Aucun'))->nullable(),
                    ])
                    ->fillForm(fn (PreRequest $r) => ['special_case' => $r->special_case])
                    ->action(function (PreRequest $r, array $data) {
                        $r->update(['special_case' => $data['special_case'] ?? null]);
                        \Filament\Notifications\Notification::make()->title(__('Cas particulier mis à jour'))->success()->send();
                    }),
                // Validité du document délivré (carte consulaire, visa, passeport) : rappel au citoyen avant échéance.
                Tables\Actions\Action::make('validity')->label(__('Validité'))->icon('heroicon-o-calendar-days')->color('gray')
                    ->visible(fn () => auth()->user()?->isSuperAdmin() || auth()->user()?->can('decide_requests'))
                    ->form([
                        Forms\Components\DatePicker::make('valid_until')->label(__('Valable jusqu’au'))->required()->after('today'),
                    ])
                    ->fillForm(fn (PreRequest $r) => ['valid_until' => $r->valid_until?->toDateString()])
                    ->action(function (PreRequest $r, array $data) {
                        $r->update(['valid_until' => $data['valid_until'], 'validity_reminded_at' => null]);
                        \Filament\Notifications\Notification::make()->title(__('Validité enregistrée'))->success()->send();
                    }),
                // Priorité urgente (cas d'extrême urgence) : la pré-demande remonte en tête de file.
                Tables\Actions\Action::make('urgent')->label(fn (PreRequest $r) => $r->is_urgent ? __('Retirer l’urgence') : __('Marquer urgent'))
                    ->icon('heroicon-o-bolt')->color('danger')
                    ->visible(fn () => auth()->user()?->isSuperAdmin() || auth()->user()?->can('decide_requests'))
                    ->requiresConfirmation()
                    ->action(function (PreRequest $r) {
                        $r->update(['is_urgent' => ! $r->is_urgent]);
                        \Filament\Notifications\Notification::make()->title($r->is_urgent ? __('Dossier marqué urgent') : __('Urgence retirée'))->success()->send();
                    }),
                // Prendre en charge : l'agent s'attribue le dossier.
                Tables\Actions\Action::make('take')->label(__('Prendre en charge'))->icon('heroicon-o-hand-raised')->color('gray')
                    ->visible(fn (PreRequest $r) => $r->status === 'submitted' && $r->assigned_to !== auth()->id() && auth()->user()?->can('decide_requests'))
                    ->requiresConfirmation()
                    ->modalHeading(fn (PreRequest $r) => __('Prendre en charge le dossier #:id ?', ['id' => $r->id]))
                    ->action(function (PreRequest $r) {
                        app(\App\Actions\AssignPreRequest::class)($r, auth()->user(), auth()->user());
                        \Filament\Notifications\Notification::make()->success()->title(__('Dossier #:id pris en charge', ['id' => $r->id]))->send();
                    }),
                // Attribuer : réservé au consul (gestion de l'équipe).
                Tables\Actions\Action::make('assign')->label(__('Attribuer'))->icon('heroicon-o-user-plus')->color('gray')
                    ->visible(fn (PreRequest $r) => $r->status === 'submitted' && $canManageTeam() && auth()->user()?->can('decide_requests'))
                    ->form(fn (PreRequest $r) => [
                        Forms\Components\Select::make('assigned_to')->label(__('Membre de l’équipe'))
                            ->placeholder(__('Personne (libérer le dossier)'))
                            ->default($r->assigned_to)
                            ->options(fn () => \App\Models\User::query()->where('consulate_id', $r->consulate_id)->where('is_active', true)
                                ->role([\App\Models\User::ROLE_CONSULATE_ADMIN, \App\Models\User::ROLE_STAFF])
                                ->orderBy('name')->pluck('name', 'id')->all()),
                    ])
                    ->action(function (PreRequest $r, array $data, Tables\Actions\Action $action) {
                        $assignee = filled($data['assigned_to'] ?? null) ? \App\Models\User::find($data['assigned_to']) : null;
                        try {
                            app(\App\Actions\AssignPreRequest::class)($r, $assignee, auth()->user());
                        } catch (\Illuminate\Validation\ValidationException $e) {
                            \Filament\Notifications\Notification::make()->danger()->title(collect($e->errors())->flatten()->first())->send();
                            $action->halt();
                        }
                        \Filament\Notifications\Notification::make()->success()
                            ->title($assignee ? __('Dossier attribué à :name', ['name' => $assignee->name]) : __('Dossier libéré'))->send();
                    }),
                // Historique complet du dossier (avec le nom des agents).
                Tables\Actions\Action::make('history')->label(__('Historique'))->icon('heroicon-o-clock')->color('gray')
                    ->modalHeading(fn (PreRequest $r) => __('Historique du dossier #:id', ['id' => $r->id]))
                    ->modalSubmitAction(false)->modalCancelActionLabel(__('Fermer'))
                    ->modalContent(fn (PreRequest $r) => view('filament.admin.pre-request-history', ['request' => $r->load(['events.user'])])),
                // Écrire au citoyen : ouvre une conversation liée au dossier dans sa messagerie.
                Tables\Actions\Action::make('message')->label(__('Écrire au citoyen'))->icon('heroicon-o-chat-bubble-left-right')->color('gray')
                    ->visible(fn () => auth()->user()?->isSuperAdmin() || auth()->user()?->can('manage_messages'))
                    ->modalHeading(fn (PreRequest $r) => __('Message à :name', ['name' => $r->citizen?->full_name]))
                    ->form(fn (PreRequest $r) => \App\Filament\Admin\Resources\ConversationResource::messageFields(
                        withSubject: true,
                        defaultSubject: __('Pré-demande #:id', ['id' => $r->id]).($r->service ? ' — '.$r->service->name : ''),
                    ))
                    ->action(function (PreRequest $r, array $data) {
                        \App\Filament\Admin\Resources\ConversationResource::send(
                            fn () => app(\App\Actions\PostConversationMessage::class)->start($r->citizen, $data['subject'], $data['body'], $r, auth()->user()),
                            __('Message envoyé au citoyen'),
                        );
                    }),
                // Demande de pièces manquantes en un clic : renvoi « à compléter » avec la liste exacte, email au citoyen.
                Tables\Actions\Action::make('requestMissing')->label(__('Demander les pièces manquantes'))->icon('heroicon-o-document-plus')->color('warning')
                    ->visible(fn (PreRequest $r) => $r->status === 'submitted'
                        && $r->documentCheck()['missing'] !== []
                        && (auth()->user()?->isSuperAdmin() || auth()->user()?->can('decide_requests')))
                    ->requiresConfirmation()
                    ->modalHeading(__('Renvoyer le dossier pour compléter ?'))
                    ->modalDescription(fn (PreRequest $r) => __('Pièces manquantes : :list', ['list' => implode(' · ', $r->documentCheck()['missing'])]))
                    ->action(function (PreRequest $r, Tables\Actions\Action $action) {
                        $missing = $r->documentCheck()['missing'];
                        try {
                            app(\App\Actions\ReviewPreRequest::class)(
                                $r, 'returned',
                                __('Pièces à déposer pour compléter le dossier : :list', ['list' => implode(' · ', $missing)]),
                                auth()->user(),
                            );
                        } catch (\Illuminate\Validation\ValidationException $e) {
                            \Filament\Notifications\Notification::make()->danger()->title(collect($e->errors())->flatten()->first())->send();
                            $action->halt();
                        }
                        \Filament\Notifications\Notification::make()->success()
                            ->title(__('Dossier renvoyé pour complément'))
                            ->body(__('Le citoyen a été prévenu par email.'))->send();
                    }),
                // Examen : seulement une pré-demande soumise ; décision, motif et email gérés par ReviewPreRequest.
                Tables\Actions\Action::make('review')->label(__('Examiner'))->icon('heroicon-o-clipboard-document-check')
                    ->visible(fn (PreRequest $r) => $r->status === 'submitted' && auth()->user()?->can('decide_requests'))
                    ->modalHeading(fn (PreRequest $r) => __('Pré-demande de :name', ['name' => $r->citizen?->full_name]))
                    ->modalDescription(fn (PreRequest $r) => __('Données saisies :').' '.(collect($r->data ?? [])->map(fn ($v, $k) => "$k : $v")->implode(' · ') ?: __('aucune')))
                    ->modalSubmitActionLabel(__('Enregistrer la décision'))
                    ->form(fn (PreRequest $r) => [
                        // Pièces exigées par le service et encore absentes du dossier.
                        Forms\Components\Placeholder::make('missing')->label(__('Pièces manquantes'))
                            ->content(function () use ($r) {
                                $check = $r->documentCheck();
                                $text = $check['missing'] ? implode(' · ', $check['missing']) : __('Aucune : toutes les pièces demandées à tous sont déposées.');

                                return $check['conditional'] ? $text.' — '.__('Selon la situation :').' '.implode(' · ', $check['conditional']) : $text;
                            }),
                        // Une décision par pièce : ouverture dans un nouvel onglet, acceptée ou refusée (motif obligatoire).
                        ...($r->documents->isEmpty()
                            ? [Forms\Components\Placeholder::make('no_docs')->label(__('Pièces jointes'))->content(__('Aucune pièce.'))]
                            : $r->documents->map(fn ($d) => Forms\Components\Section::make($d->label)
                                ->description(new \Illuminate\Support\HtmlString('<a class="text-primary-600 underline" target="_blank" rel="noopener" href="'
                                    .e(route('admin.pre-requests.document', $d)).'">'.e(__('Ouvrir')).' '.e($d->original_name).' ('.max(1, (int) round($d->size / 1024)).' Ko)</a>'))
                                ->compact()->columns(2)
                                ->schema([
                                    Forms\Components\Radio::make("doc_{$d->id}_status")->label(__('Pièce'))->inline()->live()
                                        ->options(['accepted' => __('Acceptée'), 'rejected' => __('Refusée')])
                                        ->default($d->status === 'pending' ? null : $d->status),
                                    Forms\Components\TextInput::make("doc_{$d->id}_note")->label(__('Motif du refus'))->maxLength(300)
                                        ->default($d->review_note)
                                        ->visible(fn (Forms\Get $get) => $get("doc_{$d->id}_status") === 'rejected')
                                        ->required(fn (Forms\Get $get) => $get("doc_{$d->id}_status") === 'rejected'),
                                ]))->all()),
                        Forms\Components\Select::make('decision')->label(__('Décision sur la pré-demande'))->required()->live()->options([
                            'validated' => __('Valider la pré-demande'),
                            'returned' => __('Renvoyer pour complément'),
                            'rejected' => __('Refuser'),
                        ]),
                        // Modèle de réponse : remplit le motif, qui reste modifiable.
                        Forms\Components\Select::make('template')->label(__('Insérer un modèle de réponse'))
                            ->options(fn () => \App\Models\ResponseTemplate::query()->where('is_active', true)->orderBy('title')->pluck('title', 'id')->all())
                            ->live()->dehydrated(false)
                            ->visible(fn () => \App\Models\ResponseTemplate::query()->where('is_active', true)->exists())
                            ->afterStateUpdated(function (?string $state, Forms\Set $set) {
                                if ($state && ($tpl = \App\Models\ResponseTemplate::find($state))) {
                                    $set('note', $tpl->body);
                                }
                            }),
                        Forms\Components\Textarea::make('note')->label(__('Motif ou complément demandé (visible du citoyen)'))->maxLength(500)
                            ->required(fn (Forms\Get $get) => in_array($get('decision'), ['returned', 'rejected'], true))
                            ->helperText(__('Obligatoire pour un renvoi ou un refus. Le citoyen le reçoit par email, avec les pièces refusées.')),
                    ])
                    ->action(function (PreRequest $r, array $data, Tables\Actions\Action $action) {
                        $documents = $r->documents->mapWithKeys(fn ($d) => [$d->id => [
                            'status' => $data["doc_{$d->id}_status"] ?? null,
                            'note' => $data["doc_{$d->id}_note"] ?? null,
                        ]])->all();
                        try {
                            app(\App\Actions\ReviewPreRequest::class)($r, $data['decision'], $data['note'] ?? null, auth()->user(), $documents);
                        } catch (\Illuminate\Validation\ValidationException $e) {
                            \Filament\Notifications\Notification::make()->danger()->title(collect($e->errors())->flatten()->first())->send();
                            $action->halt();
                        }
                        \Filament\Notifications\Notification::make()->success()
                            ->title(__('Décision enregistrée : :decision', ['decision' => __(PreRequest::STATUSES[$data['decision']] ?? $data['decision'])]))
                            ->body(__('Le citoyen a été prévenu par email.'))->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListPreRequests::route('/')];
    }
}

<?php

namespace App\Filament\Admin\Resources;

use App\Actions\PostConversationMessage;
use App\Filament\Admin\Resources\ConversationResource\Pages;
use App\Models\Conversation;
use App\Models\ResponseTemplate;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/** Messagerie citoyens : conversations ouvertes depuis l'espace citoyen ou depuis une pré-demande. */
class ConversationResource extends Resource
{
    use \App\Filament\Concerns\TranslatedResourceLabels;

    protected static ?string $model = Conversation::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Messagerie citoyens';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $modelLabel = 'conversation';

    protected static ?string $pluralModelLabel = 'conversations';

    protected static ?int $navigationSort = 0;

    public static function getNavigationBadge(): ?string
    {
        $count = Conversation::query()->where('unread_by_staff', true)->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['citizen', 'preRequest']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_message_at', 'desc')
            ->recordUrl(fn (Conversation $r) => static::getUrl('view', ['record' => $r]))
            ->columns([
                Tables\Columns\IconColumn::make('unread_by_staff')->label(__('Nouveau'))
                    ->icon(fn (bool $state) => $state ? 'heroicon-s-envelope' : 'heroicon-o-envelope-open')
                    ->color(fn (bool $state) => $state ? 'warning' : 'gray'),
                Tables\Columns\TextColumn::make('subject')->label(__('Sujet'))->searchable()->limit(60)
                    ->weight(fn (Conversation $r) => $r->unread_by_staff ? 'bold' : null),
                Tables\Columns\TextColumn::make('citizen.full_name')->label(__('Citoyen'))
                    ->description(fn (Conversation $r) => $r->citizen?->email)
                    ->searchable(query: fn (Builder $q, string $search) => $q->whereHas('citizen', fn ($c) => $c->withoutGlobalScopes()
                        ->where('full_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))),
                Tables\Columns\TextColumn::make('pre_request_id')->label(__('Dossier'))
                    ->formatStateUsing(fn ($state) => $state ? '#'.$state : '—')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->label(__('Statut'))->badge()
                    ->formatStateUsing(fn (string $state) => __(Conversation::STATUSES[$state] ?? $state))
                    ->color(fn (string $state) => $state === 'open' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('last_message_at')->label(__('Dernier message'))->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('consulate.slug')->label(__('Consulat'))->badge()
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label(__('Statut'))
                    ->options(fn () => collect(Conversation::STATUSES)->map(fn ($l) => __($l))->all())
                    ->default('open'),
                Tables\Filters\Filter::make('unread')->label(__('Non lues'))->toggle()
                    ->query(fn (Builder $q) => $q->where('unread_by_staff', true)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label(__('Ouvrir')),
            ]);
    }

    /** Formulaire de réponse (partagé par la page de conversation et l'action « Écrire au citoyen »). */
    public static function messageFields(bool $withSubject = false, ?string $defaultSubject = null): array
    {
        $fields = [];
        if ($withSubject) {
            $fields[] = Forms\Components\TextInput::make('subject')->label(__('Sujet'))->required()->maxLength(150)->default($defaultSubject);
        }
        $fields[] = Forms\Components\Select::make('template')->label(__('Insérer un modèle de réponse'))
            ->options(fn () => ResponseTemplate::query()->where('is_active', true)->orderBy('title')->pluck('title', 'id'))
            ->visible(fn () => ResponseTemplate::query()->where('is_active', true)->exists())
            ->live()->dehydrated(false)
            ->afterStateUpdated(function ($state, Forms\Set $set) {
                if ($state && ($tpl = ResponseTemplate::query()->find($state))) {
                    $set('body', $tpl->body);
                }
            });
        $fields[] = Forms\Components\Textarea::make('body')->label(__('Message'))->required()->rows(6)
            ->maxLength(PostConversationMessage::MAX_BODY)
            ->helperText(__('Le citoyen est prévenu par email et lit le message dans son espace. Votre nom n’est pas affiché : la réponse est signée « Consulat ».'));

        return $fields;
    }

    /** Exécute un envoi et affiche le résultat (message d'erreur métier en notification). */
    public static function send(callable $fn, string $success): bool
    {
        try {
            $fn();
            Notification::make()->success()->title($success)->send();

            return true;
        } catch (ValidationException $e) {
            Notification::make()->danger()->title(collect($e->errors())->flatten()->first())->send();

            return false;
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConversations::route('/'),
            'view' => Pages\ViewConversation::route('/{record}'),
        ];
    }
}

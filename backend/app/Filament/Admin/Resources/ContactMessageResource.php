<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ContactMessageResource\Pages;
use App\Models\ContactMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactMessageResource extends Resource
{
    use \App\Filament\Concerns\TranslatedResourceLabels;

    protected static ?string $model = ContactMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Messages reçus';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $modelLabel = 'message';

    protected static ?string $pluralModelLabel = 'messages de contact';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ContactMessage::query()->where('is_read', false)->count();

        return $count ?: null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label(__('Nom')),
                Forms\Components\TextInput::make('email')->label(__('Email')),
                Forms\Components\TextInput::make('phone')->label(__('Téléphone')),
                Forms\Components\TextInput::make('subject')->label(__('Sujet')),
                Forms\Components\Textarea::make('message')->label(__('Message'))->rows(8)->columnSpanFull(),
                Forms\Components\Toggle::make('is_read')->label(__('Lu')),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            Tables\Columns\IconColumn::make('is_read')->label(__('Lu'))->boolean(),
            Tables\Columns\TextColumn::make('name')->label(__('Nom'))->searchable(),
            Tables\Columns\TextColumn::make('subject')->label(__('Sujet'))->searchable()->limit(50),
            Tables\Columns\TextColumn::make('email')->label(__('Email')),
            Tables\Columns\TextColumn::make('consulate.slug')->label(__('Consulat'))->badge()
                ->visible(fn () => auth()->user()?->isSuperAdmin()),
            Tables\Columns\TextColumn::make('created_at')->label(__('Reçu le'))->dateTime('d/m/Y H:i')->sortable(),
        ])->filters([
            Tables\Filters\TernaryFilter::make('is_read')->label(__('Lu')),
        ])->actions([
            Tables\Actions\ViewAction::make()->label(__('Ouvrir')),
            Tables\Actions\Action::make('toggle')->label(fn (ContactMessage $r) => $r->is_read ? 'Marquer non lu' : 'Marquer lu')
                ->icon('heroicon-o-eye')->color('gray')
                ->action(fn (ContactMessage $r) => $r->update(['is_read' => ! $r->is_read])),
            Tables\Actions\DeleteAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkAction::make('read')->label(__('Marquer comme lus'))
                ->action(fn ($records) => $records->each->update(['is_read' => true]))
                ->deselectRecordsAfterCompletion(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactMessages::route('/'),
            'view' => Pages\ViewContactMessage::route('/{record}'),
        ];
    }
}

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
    protected static ?string $model = ContactMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Rendez-vous';

    protected static ?string $modelLabel = 'message';

    protected static ?string $pluralModelLabel = 'messages de contact';

    protected static ?int $navigationSort = 3;

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
                Forms\Components\TextInput::make('name')->label('Nom'),
                Forms\Components\TextInput::make('email')->label('Email'),
                Forms\Components\TextInput::make('phone')->label('Téléphone'),
                Forms\Components\TextInput::make('subject')->label('Sujet'),
                Forms\Components\Textarea::make('message')->label('Message')->rows(8)->columnSpanFull(),
                Forms\Components\Toggle::make('is_read')->label('Lu'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            Tables\Columns\IconColumn::make('is_read')->label('Lu')->boolean(),
            Tables\Columns\TextColumn::make('name')->label('Nom')->searchable(),
            Tables\Columns\TextColumn::make('subject')->label('Sujet')->searchable()->limit(50),
            Tables\Columns\TextColumn::make('email')->label('Email'),
            Tables\Columns\TextColumn::make('consulate.slug')->label('Consulat')->badge()
                ->visible(fn () => auth()->user()?->isSuperAdmin()),
            Tables\Columns\TextColumn::make('created_at')->label('Reçu le')->dateTime('d/m/Y H:i')->sortable(),
        ])->filters([
            Tables\Filters\TernaryFilter::make('is_read')->label('Lu'),
        ])->actions([
            Tables\Actions\ViewAction::make()->label('Ouvrir'),
            Tables\Actions\Action::make('toggle')->label(fn (ContactMessage $r) => $r->is_read ? 'Marquer non lu' : 'Marquer lu')
                ->icon('heroicon-o-eye')->color('gray')
                ->action(fn (ContactMessage $r) => $r->update(['is_read' => ! $r->is_read])),
            Tables\Actions\DeleteAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkAction::make('read')->label('Marquer comme lus')
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

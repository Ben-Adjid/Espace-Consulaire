<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\HasConsulateField;
use App\Filament\Admin\Resources\NewsResource\Pages;
use App\Models\News;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class NewsResource extends Resource
{
    use HasConsulateField, Translatable;

    protected static ?string $model = News::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationGroup = 'Contenu';

    protected static ?string $modelLabel = 'actualité';

    protected static ?string $pluralModelLabel = 'actualités';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                static::consulateField(),
                Forms\Components\TextInput::make('title')->label('Titre')->required()->maxLength(200)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get, $operation) {
                        if ($operation === 'create' && ! $get('slug')) {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                Forms\Components\TextInput::make('slug')->label('Identifiant URL')->required()->alphaDash()->maxLength(160),
                Forms\Components\DateTimePicker::make('published_at')->label('Date de publication')->default(now()),
                Forms\Components\FileUpload::make('image')->label('Image')->image()->disk('public')
                    ->directory('news')->maxSize(2048)->imageResizeMode('cover')->columnSpanFull(),
                Forms\Components\Textarea::make('excerpt')->label('Chapeau')->rows(2)->maxLength(300)->columnSpanFull(),
                Forms\Components\Textarea::make('body')->label('Contenu')->rows(10)->columnSpanFull()
                    ->helperText('Texte brut : une ligne vide sépare les paragraphes.'),
                Forms\Components\Toggle::make('is_published')->label('Publié')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('published_at', 'desc')->columns([
            Tables\Columns\ImageColumn::make('image')->label('')->disk('public')->square(),
            Tables\Columns\TextColumn::make('title')->label('Titre')->searchable()->limit(60),
            Tables\Columns\TextColumn::make('consulate.slug')->label('Consulat')->badge()
                ->visible(fn () => auth()->user()?->isSuperAdmin()),
            Tables\Columns\TextColumn::make('published_at')->label('Publication')->dateTime('d/m/Y H:i')->sortable(),
            Tables\Columns\IconColumn::make('is_published')->label('Publié')->boolean(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNews::route('/'),
            'create' => Pages\CreateNews::route('/create'),
            'edit' => Pages\EditNews::route('/{record}/edit'),
        ];
    }
}

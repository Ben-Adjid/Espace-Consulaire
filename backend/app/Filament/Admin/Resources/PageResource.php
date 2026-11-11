<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\HasConsulateField;
use App\Filament\Admin\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PageResource extends Resource
{
    use HasConsulateField, Translatable;

    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'Contenu';

    protected static ?string $modelLabel = 'page';

    protected static ?string $pluralModelLabel = 'pages (légales…)';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                static::consulateField(),
                Forms\Components\TextInput::make('title')->label('Titre')->required()->maxLength(200),
                Forms\Components\TextInput::make('slug')->label('Identifiant URL')->required()->alphaDash()->maxLength(120)
                    ->helperText('Ex. mentions-legales, confidentialite, demo-non-officielle'),
                Forms\Components\Textarea::make('body')->label('Contenu')->rows(14)->columnSpanFull()
                    ->helperText('Texte brut : une ligne vide sépare les paragraphes.'),
                Forms\Components\Toggle::make('is_published')->label('Publiée')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->label('Titre')->searchable(),
            Tables\Columns\TextColumn::make('slug')->label('Slug')->badge(),
            Tables\Columns\TextColumn::make('consulate.slug')->label('Consulat')->badge()
                ->visible(fn () => auth()->user()?->isSuperAdmin()),
            Tables\Columns\IconColumn::make('is_published')->label('Publiée')->boolean(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}

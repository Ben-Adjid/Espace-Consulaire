<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\HasConsulateField;
use App\Filament\Admin\Resources\FaqResource\Pages;
use App\Models\Faq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FaqResource extends Resource
{
    use \App\Filament\Concerns\TranslatedResourceLabels;

    use HasConsulateField, Translatable;

    protected static ?string $model = Faq::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationLabel = 'Questions fréquentes';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $modelLabel = 'question FAQ';

    protected static ?string $pluralModelLabel = 'FAQ';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                static::consulateField(),
                Forms\Components\TextInput::make('question')->label(__('Question'))->required()->maxLength(255)->columnSpanFull(),
                Forms\Components\Textarea::make('answer')->label(__('Réponse'))->required()->rows(6)->columnSpanFull(),
                Forms\Components\TextInput::make('sort_order')->label(__('Ordre'))->numeric()->default(0),
                Forms\Components\Toggle::make('is_published')->label(__('Publiée'))->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sort_order')->columns([
            Tables\Columns\TextColumn::make('question')->label(__('Question'))->searchable()->limit(80),
            Tables\Columns\TextColumn::make('consulate.slug')->label(__('Consulat'))->badge()
                ->visible(fn () => auth()->user()?->isSuperAdmin()),
            Tables\Columns\TextColumn::make('sort_order')->label(__('Ordre'))->sortable(),
            Tables\Columns\IconColumn::make('is_published')->label(__('Publiée'))->boolean(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFaqs::route('/'),
            'create' => Pages\CreateFaq::route('/create'),
            'edit' => Pages\EditFaq::route('/{record}/edit'),
        ];
    }
}

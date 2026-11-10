<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\HasConsulateField;
use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    use HasConsulateField;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $modelLabel = 'utilisateur';

    protected static ?string $pluralModelLabel = 'utilisateurs';

    protected static ?int $navigationSort = 2;

    /**
     * Un consulate_admin ne voit que les comptes de son consulat, sans les super_admin.
     * (User n'a pas de global scope : filtre explicite ici.)
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['roles', 'consulate']);
        $user = auth()->user();

        if ($user && ! $user->isSuperAdmin()) {
            $query->where('consulate_id', $user->consulate_id ?? 0)
                ->whereDoesntHave('roles', fn ($q) => $q->where('name', User::ROLE_SUPER_ADMIN));
        }

        return $query;
    }

    /** Rôles attribuables : le super_admin choisit librement, un consulate_admin crée du staff / admins de son consulat. */
    public static function assignableRoles(): array
    {
        $all = [
            User::ROLE_SUPER_ADMIN => 'Super administrateur',
            User::ROLE_CONSULATE_ADMIN => 'Administrateur du consulat',
            User::ROLE_STAFF => 'Agent',
        ];

        if (auth()->user()?->isSuperAdmin()) {
            return $all;
        }

        unset($all[User::ROLE_SUPER_ADMIN]);

        return $all;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label('Nom')->required()->maxLength(120),
                Forms\Components\TextInput::make('email')->label('Email')->email()->required()
                    ->unique(ignoreRecord: true)->maxLength(160),
                Forms\Components\TextInput::make('password')->label('Mot de passe')->password()->revealable()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->minLength(10)
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->helperText('10 caractères minimum. Laisser vide pour ne pas changer.'),
                Forms\Components\Select::make('role')->label('Rôle')->options(fn () => static::assignableRoles())
                    ->required()->dehydrated(false)->native(false),
                static::consulateField(),
                Forms\Components\Toggle::make('is_active')->label('Compte actif')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Nom')->searchable(),
            Tables\Columns\TextColumn::make('email')->label('Email')->searchable(),
            Tables\Columns\TextColumn::make('roles.name')->label('Rôle')->badge(),
            Tables\Columns\TextColumn::make('consulate.slug')->label('Consulat')->badge()->placeholder('—'),
            Tables\Columns\IconColumn::make('is_active')->label('Actif')->boolean(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

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
    use \App\Filament\Concerns\TranslatedResourceLabels;

    use HasConsulateField;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Équipe et comptes';

    protected static ?string $navigationGroup = 'Paramétrage';

    protected static ?string $modelLabel = 'utilisateur';

    protected static ?string $pluralModelLabel = 'utilisateurs';

    protected static ?int $navigationSort = 5;

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
            User::ROLE_SECRETARIAT => 'Secrétariat (rendez-vous et messagerie)',
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
                Forms\Components\TextInput::make('name')->label(__('Nom'))->required()->maxLength(120),
                Forms\Components\TextInput::make('email')->label(__('Email'))->email()->required()
                    ->unique(ignoreRecord: true)->maxLength(160),
                Forms\Components\TextInput::make('password')->label(__('Mot de passe'))->password()->revealable()
                    ->minLength(10)
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->helperText(fn (string $operation) => $operation === 'create'
                        ? __('Recommandé : laisser vide. La personne reçoit un lien d’activation par email et choisit elle-même son mot de passe.')
                        : __('10 caractères minimum. Laisser vide pour ne pas changer.')),
                Forms\Components\Select::make('role')->label(__('Rôle'))->options(fn () => static::assignableRoles())
                    ->required()->dehydrated(false)->native(false),
                static::consulateField(),
                Forms\Components\Toggle::make('is_active')->label(__('Compte actif'))->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label(__('Nom'))->searchable(),
            Tables\Columns\TextColumn::make('email')->label(__('Email'))->searchable(),
            Tables\Columns\TextColumn::make('roles.name')->label(__('Rôle'))->badge(),
            Tables\Columns\TextColumn::make('consulate.slug')->label(__('Consulat'))->badge()->placeholder(__('—')),
            Tables\Columns\IconColumn::make('is_active')->label(__('Actif'))->boolean(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            // (Ré)envoi du lien d'activation : la personne choisit son mot de passe, valable 60 minutes.
            Tables\Actions\Action::make('invite')->label(__('Envoyer le lien d’activation'))->icon('heroicon-o-envelope')->color('gray')
                ->requiresConfirmation()
                ->modalDescription(fn (User $r) => 'Un email avec un lien pour choisir son mot de passe sera envoyé à '.$r->email.'.')
                ->action(function (User $r) {
                    app(\App\Actions\SendAdminInvitation::class)->execute($r);
                    \Filament\Notifications\Notification::make()->success()->title('Lien d’activation envoyé à '.$r->email)->send();
                }),
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

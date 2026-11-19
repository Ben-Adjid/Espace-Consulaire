<?php

namespace App\Filament\Admin\Pages;

use App\Actions\CreateConsulateFromTemplate;
use App\Models\Consulate;
use App\Models\ConsulatePayment;
use Carbon\Carbon;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;

/**
 * Assistant de création d'un consulat (super admin) : identité, services, créneaux, puis invitation du consul.
 * Le premier paiement est saisi dans le même parcours : il prolonge l'abonnement et active le consulat.
 */
class NouveauConsulat extends Page implements HasForms
{
    use \App\Filament\Concerns\TranslatedPageLabels;

    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';

    protected static ?string $navigationGroup = 'Pilotage';

    protected static ?string $navigationLabel = 'Nouveau consulat';

    protected static ?string $title = 'Créer un consulat';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.admin.pages.nouveau-consulat';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isSuperAdmin();
    }

    public function mount(): void
    {
        $this->form->fill([
            'represented_country' => 'Tchad',
            'languages' => ['fr'],
            'slots' => [
                ['weekdays' => [1, 2, 3, 4, 5], 'start_time' => '09:00', 'end_time' => '12:00', 'capacity' => 6],
                ['weekdays' => [1, 2, 3, 4, 5], 'start_time' => '14:00', 'end_time' => '16:00', 'capacity' => 4],
            ],
            'services' => ['passeport', 'carte-consulaire', 'visa'],
            // Valeurs de départ du premier paiement : sans elles, fill() efface les défauts des champs.
            'payment' => [
                'amount' => config('subscription.default_amount') ?: null,
                'currency' => config('subscription.default_currency'),
                'paid_at' => now()->toDateString(),
                'period_start' => now()->toDateString(),
                'period_end' => now()->addYear()->subDay()->toDateString(),
            ],
        ]);
    }

    public function form(Form $form): Form
    {
        $weekdays = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi'];

        return $form->schema([
            Forms\Components\Wizard::make([
                Forms\Components\Wizard\Step::make('Identité')->label(__('Identité'))->schema([
                    Forms\Components\TextInput::make('name')->label(__('Nom du consulat (français)'))->required()->maxLength(160),
                    Forms\Components\TextInput::make('slug')->label(__('Identifiant dans l’URL'))->required()->alphaDash()->maxLength(60)
                        ->unique('consulates', 'slug')->helperText(__('Ex. tchad-canada : utilisé dans l’adresse du site.')),
                    Forms\Components\TextInput::make('ref_prefix')->label(__('Préfixe des références'))->required()->alphaDash()->maxLength(12)
                        ->unique('consulates', 'ref_prefix')->helperText(__('Ex. TCH-CA : base des références de rendez-vous et de reçus.')),
                    Forms\Components\TextInput::make('represented_country')->label(__('Pays représenté'))->required()->maxLength(80),
                    Forms\Components\TextInput::make('host_country')->label(__('Pays d’accueil'))->required()->maxLength(80),
                    Forms\Components\TagsInput::make('covered_countries')->label(__('Pays de résidence rattachés'))->required()
                        ->placeholder(__('Ajouter un pays puis Entrée'))->helperText(__('Les citoyens résidant dans ces pays sont rattachés à ce consulat.')),
                    Forms\Components\CheckboxList::make('languages')->label(__('Langues du site'))->required()
                        ->options(['fr' => __('Français'), 'en' => 'English', 'ar' => 'العربية'])->columns(3),
                    Forms\Components\TextInput::make('email')->label(__('Email de contact'))->email()->maxLength(160),
                    Forms\Components\TextInput::make('phone')->label(__('Téléphone'))->tel()->maxLength(32),
                    Forms\Components\Textarea::make('address')->label(__('Adresse'))->rows(2)->maxLength(300),
                ])->columns(2),

                Forms\Components\Wizard\Step::make('Services')->label(__('Services'))->schema([
                    Forms\Components\CheckboxList::make('services')->label(__('Services ouverts aux rendez-vous'))->required()
                        ->options([
                            'passeport' => __('Passeport'),
                            'carte-consulaire' => __('Carte consulaire'),
                            'visa' => __('Visa'),
                        ])
                        ->helperText(__('Les intitulés et les pièces types sont repris du modèle commun ; le consulat les affine ensuite.')),
                ]),

                Forms\Components\Wizard\Step::make('Créneaux')->label(__('Créneaux'))->schema([
                    Forms\Components\Repeater::make('slots')->label(__('Créneaux hebdomadaires'))->required()->minItems(1)
                        ->columns(4)
                        ->schema([
                            Forms\Components\Select::make('weekdays')->label(__('Jours'))->multiple()->required()->options($weekdays),
                            Forms\Components\TextInput::make('start_time')->label(__('De (HH:MM)'))->placeholder(__('09:00'))
                                ->regex('/^([01]\d|2[0-3]):[0-5]\d$/')->required(),
                            Forms\Components\TextInput::make('end_time')->label(__('À (HH:MM)'))->placeholder(__('12:00'))
                                ->regex('/^([01]\d|2[0-3]):[0-5]\d$/')->required(),
                            Forms\Components\TextInput::make('capacity')->label(__('Rendez-vous par créneau'))->numeric()->minValue(1)->required(),
                        ]),
                ]),

                Forms\Components\Wizard\Step::make('Abonnement')->label(__('Abonnement'))->schema([
                    Forms\Components\TextInput::make('payment.amount')->label(__('Montant reçu'))->numeric()->minValue(0)->required()
                        ->default(fn () => config('subscription.default_amount') ?: null),
                    Forms\Components\TextInput::make('payment.currency')->label(__('Devise'))->required()->maxLength(8)
                        ->default(fn () => config('subscription.default_currency')),
                    Forms\Components\DatePicker::make('payment.paid_at')->label(__('Reçu le'))->required()
                        ->default(now()->toDateString())->maxDate(now()),
                    Forms\Components\DatePicker::make('payment.period_start')->label(__('Période du'))->required()->live()
                        ->default(now()->toDateString())
                        ->afterStateUpdated(fn (?string $state, Forms\Set $set) => $state
                            ? $set('payment.period_end', Carbon::parse($state)->addYear()->subDay()->toDateString())
                            : null),
                    Forms\Components\DatePicker::make('payment.period_end')->label(__('au'))->required()
                        ->default(now()->addYear()->subDay()->toDateString())
                        ->afterOrEqual('payment.period_start'),
                    Forms\Components\Select::make('payment.method')->label(__('Mode de paiement'))->required()
                        ->options(ConsulatePayment::METHODS),
                    Forms\Components\TextInput::make('payment.reference')->label(__('Référence (virement, reçu…)'))->maxLength(80),
                ])->columns(2)
                    ->description(__('Le premier paiement prolonge l’abonnement d’un an et active le consulat dans la même opération.')),

                Forms\Components\Wizard\Step::make('Consul')->label(__('Consul'))->schema([
                    Forms\Components\TextInput::make('consul_name')->label(__('Nom du consul'))->required()->maxLength(120),
                    Forms\Components\TextInput::make('consul_email')->label(__('Email du consul'))->email()->required()
                        ->unique(User::class, 'email')->helperText(__('Il reçoit un lien d’activation pour choisir son mot de passe.')),
                ])->columns(2),
            ])->submitAction(new HtmlString(Blade::render(
                '<x-filament::button type="submit" icon="heroicon-m-check">'.e(__('Créer le consulat')).'</x-filament::button>'
            ))),
        ])->statePath('data');
    }

    public function create(): void
    {
        $state = $this->form->getState();

        $consulate = app(CreateConsulateFromTemplate::class)($state, auth()->id());

        Notification::make()
            ->title(__('Consulat créé'))
            ->body("{$consulate->name} est créé (abonnement d’un an enregistré, consulat actif). Le consul a reçu son lien d’activation.")
            ->success()
            ->send();

        $this->mount();
    }
}

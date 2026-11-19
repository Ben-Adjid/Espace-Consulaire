<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Pages;
use App\Filament\Admin\Resources;
use App\Models\User;
use Filament\Widgets\Widget;

/**
 * Accueil du tableau de bord : salutation, rôle, consulat, et raccourcis.
 * Les raccourcis sont filtrés par les mêmes contrôles que le menu (canAccess / canViewAny) :
 * chaque rôle ne voit que les fonctions qui lui sont réellement ouvertes.
 */
class WelcomeWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.welcome';

    protected static ?int $sort = -10;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    /** Ordre de priorité des raccourcis ; les premiers autorisés sont affichés. */
    private const CANDIDATES = [
        [Pages\FileDuJour::class, 'File du jour', 'heroicon-o-queue-list', 'page'],
        [Resources\AppointmentResource::class, 'Rendez-vous', 'heroicon-o-calendar-days', 'resource'],
        [Resources\PreRequestResource::class, 'Pré-demandes', 'heroicon-o-document-text', 'resource'],
        [Resources\CitizenResource::class, 'Comptes citoyens', 'heroicon-o-identification', 'resource'],
        [Resources\ContactMessageResource::class, 'Messages', 'heroicon-o-envelope', 'resource'],
        [Pages\NouveauConsulat::class, 'Nouveau consulat', 'heroicon-o-plus-circle', 'page'],
        // Libellé du menu : « Consulats » (super admin) ou « Mon consulat » (consul).
        [Resources\ConsulateResource::class, null, 'heroicon-o-building-library', 'resource'],
        [Resources\ConsulatePaymentResource::class, 'Paiements d’abonnement', 'heroicon-o-banknotes', 'resource'],
        [Pages\TableauGlobal::class, 'Tableau global', 'heroicon-o-globe-alt', 'page'],
        [Resources\CitizenReportResource::class, 'Signalements', 'heroicon-o-flag', 'resource'],
        [Resources\UserResource::class, 'Équipe', 'heroicon-o-users', 'resource'],
        [Pages\MonAbonnement::class, 'Mon abonnement', 'heroicon-o-credit-card', 'page'],
        [Pages\RapportMensuel::class, 'Rapport mensuel', 'heroicon-o-document-chart-bar', 'page'],
    ];

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();

        $roleLabel = match (true) {
            $user->isSuperAdmin() => __('Super administrateur · toute la plateforme'),
            $user->hasRole(User::ROLE_CONSULATE_ADMIN) => __('Consul'),
            default => __('Agent consulaire'),
        };

        $shortcuts = [];
        foreach (self::CANDIDATES as [$class, $label, $icon, $kind]) {
            $allowed = $kind === 'page' ? $class::canAccess() : $class::canViewAny();
            if ($allowed) {
                $shortcuts[] = ['label' => $label !== null ? __($label) : $class::getNavigationLabel(), 'icon' => $icon, 'url' => $class::getUrl()];
            }
            if (count($shortcuts) === 6) {
                break;
            }
        }

        $hour = (int) now()->format('G');

        return [
            'greeting' => $hour < 18 ? __('Bonjour') : __('Bonsoir'),
            'name' => $user->name,
            'roleLabel' => $roleLabel,
            'consulate' => $user->isSuperAdmin() ? null : $user->consulate?->getTranslation('name', app()->getLocale()),
            'date' => now()->locale(app()->getLocale())->isoFormat('dddd D MMMM YYYY'),
            'shortcuts' => $shortcuts,
        ];
    }
}

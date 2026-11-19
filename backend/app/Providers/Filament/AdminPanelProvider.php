<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Widgets\ConsulateStatsWidget;
use App\Filament\Admin\Widgets\UpcomingAppointmentsWidget;
use App\Filament\Admin\Widgets\AppointmentsByServiceChart;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Navigation\MenuItem;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Filament\SpatieLaravelTranslatablePlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /** Site public du consulat de l'utilisateur connecté (ou racine de la plateforme pour le super_admin). */
    public static function publicSiteUrl(): string
    {
        $base = rtrim((string) config('app.frontend_url'), '/');
        $user = auth()->user();

        if ($user && ! $user->isSuperAdmin() && $user->consulate) {
            return $base.'/'.$user->consulate->slug.'/fr';
        }

        return $base;
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            // « Mot de passe oublié » pour le personnel, et pages utilisées par les liens d'activation.
            ->passwordReset()
            ->userMenuItems([
                MenuItem::make()
                    ->label(fn () => __('Voir le site public'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn () => static::publicSiteUrl())
                    ->openUrlInNewTab(),
                MenuItem::make()
                    ->label(fn () => __('Sécurité du compte (double authentification)'))
                    ->icon('heroicon-o-shield-check')
                    ->url(fn () => \App\Filament\Admin\Pages\TwoFactorSettings::getUrl()),
            ])
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn () => view('filament.auth.login-links'),
            )
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn () => view('filament.theme-styles'))
            // Téléphone et tablette : le menu latéral démarre fermé (il recouvrirait sinon le contenu).
            ->renderHook(PanelsRenderHook::SCRIPTS_AFTER, fn () => new \Illuminate\Support\HtmlString(
                "<script>document.addEventListener('alpine:initialized',()=>{if(window.innerWidth<1024&&window.Alpine.store('sidebar')){window.Alpine.store('sidebar').close()}})</script>"
            ))
            // Identité commune avec les sites publics : vert profond, accents dorés, interface claire.
            ->brandName(fn () => __('Espace consulaire — Administration'))
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.5rem')
            ->darkMode(false)
            ->colors([
                'primary' => Color::hex('#1b5148'),
                'warning' => Color::Amber,
                'danger' => Color::Rose,
                'success' => Color::Emerald,
                'gray' => Color::Slate,
            ])
            // Menu latéral repliable sur ordinateur, recherche globale au clavier.
            ->sidebarCollapsibleOnDesktop()
            ->globalSearchKeyBindings(['ctrl+k', 'command+k'])
            // Catégories métier, dans l'ordre d'usage ; libellés traduits à l'affichage (même clé que les rubriques).
            ->navigationGroups(array_map(
                fn (string $group) => \Filament\Navigation\NavigationGroup::make($group)->label(fn () => __($group)),
                ['Accueil du public', 'Dossiers et citoyens', 'Communication', 'Paramétrage', 'Pilotage'],
            ))
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn () => view('filament.language-switch'))
            ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, fn () => new \Illuminate\Support\HtmlString(
                '<div style="display:flex;justify-content:center;margin-bottom:1rem;">'.view('filament.language-switch')->render().'</div>'
            ))
            ->plugin(
                SpatieLaravelTranslatablePlugin::make()->defaultLocales(['fr', 'en', 'ar'])
            )
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                // Chaque rôle voit ses widgets (canView) : plateforme, travail du jour, pilotage du consulat.
                \App\Filament\Admin\Widgets\WelcomeWidget::class,
                \App\Filament\Admin\Widgets\PlatformOverviewWidget::class,
                \App\Filament\Admin\Widgets\TodayWorkWidget::class,
                \App\Filament\Admin\Widgets\MonthlyActivityWidget::class,
                ConsulateStatsWidget::class,
                \App\Filament\Admin\Widgets\SubscriptionStatusWidget::class,
                UpcomingAppointmentsWidget::class,
                AppointmentsByServiceChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            // Langue de l'interface (FR / EN / AR, arabe de droite à gauche), aussi pour les requêtes Livewire.
            ->middleware([\App\Http\Middleware\SetPanelLocale::class], isPersistent: true)
            ->authMiddleware([
                Authenticate::class,
                // Double authentification : code exigé après chaque connexion si activée.
                \App\Http\Middleware\EnsureTwoFactorVerified::class,
            ], isPersistent: true);
    }
}

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
            ->login()
            ->userMenuItems([
                MenuItem::make()
                    ->label('Voir le site public')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn () => static::publicSiteUrl())
                    ->openUrlInNewTab(),
            ])
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn () => view('filament.auth.login-links'),
            )
            ->brandName('Consulats — Administration (démo)')
            ->colors(['primary' => Color::Indigo])
            ->navigationGroups(['Contenu', 'Rendez-vous', 'Administration'])
            ->plugin(
                SpatieLaravelTranslatablePlugin::make()->defaultLocales(['fr', 'en', 'ar'])
            )
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                ConsulateStatsWidget::class,
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
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

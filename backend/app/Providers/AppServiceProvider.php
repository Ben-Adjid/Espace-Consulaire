<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Appointment;
use App\Models\ClosedDate;
use App\Models\Consulate;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\News;
use App\Models\Page;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Observers\AdminActivityObserver;
use App\Services\ConsulateContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Translatable\Facades\Translatable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Une seule instance par requête : le middleware et le contrôleur doivent voir le même consulat.
        $this->app->singleton(ConsulateContext::class);
    }

    public function boot(): void
    {
        foreach ([
            Appointment::class,
            ClosedDate::class,
            Consulate::class,
            ContactMessage::class,
            Faq::class,
            News::class,
            Page::class,
            Service::class,
            TimeSlot::class,
            User::class,
        ] as $model) {
            $model::observe(AdminActivityObserver::class);
        }

        // Le super_admin peut tout faire (court-circuite les policies).
        Gate::before(fn (User $user) => $user->isSuperAdmin() ? true : null);

        // Contenu non traduit : on retombe sur le français plutôt que d'afficher du vide.
        Translatable::fallback(fallbackLocale: 'fr', fallbackAny: true);

        // Limiteurs de débit des formulaires publics (par IP).
        RateLimiter::for('contact', fn (Request $r) => Limit::perMinute(5)->by($r->ip()));
        RateLimiter::for('booking', fn (Request $r) => Limit::perMinute(10)->by($r->ip()));
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\Consulate;
use App\Services\ConsulateContext;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Charge le consulat demandé par l'URL (/{consulate_slug}), fixe la langue
 * (paramètre ?lang= puis session), la direction du texte (RTL pour l'arabe)
 * et partage le thème avec toutes les vues.
 */
class SetCurrentConsulate
{
    public const RTL_LOCALES = ['ar'];

    public function __construct(private ConsulateContext $context)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $slug = (string) $request->route('consulate');

        $consulate = Consulate::query()->where('slug', $slug)->where('is_active', true)->first();

        abort_if($consulate === null, 404);

        $this->context->set($consulate);

        // Le paramètre de route est fourni automatiquement à route() : /{consulate}/…
        URL::defaults(['consulate' => $consulate->slug]);
        $request->route()->forgetParameter('consulate');

        $locale = $this->resolveLocale($request, $consulate);
        self::applyLocale($locale);

        View::share([
            'consulate' => $consulate,
            'locale' => $locale,
            'dir' => in_array($locale, self::RTL_LOCALES, true) ? 'rtl' : 'ltr',
        ]);

        return $next($request);
    }

    private function resolveLocale(Request $request, Consulate $consulate): string
    {
        $available = $consulate->availableLocales();

        $requested = $request->query('lang');
        if (is_string($requested) && in_array($requested, $available, true)) {
            $request->session()->put('locale', $requested);

            return $requested;
        }

        $stored = $request->session()->get('locale');
        if (is_string($stored) && in_array($stored, $available, true)) {
            return $stored;
        }

        return in_array('fr', $available, true) ? 'fr' : $available[0];
    }

    public static function applyLocale(string $locale): void
    {
        App::setLocale($locale);
        Carbon::setLocale($locale);
    }
}

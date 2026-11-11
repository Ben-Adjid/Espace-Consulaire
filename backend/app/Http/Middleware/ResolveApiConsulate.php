<?php

namespace App\Http\Middleware;

use App\Http\Middleware\SetCurrentConsulate;
use App\Models\Consulate;
use App\Services\ConsulateContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API publique : résout le consulat depuis le paramètre de route {consulate} et fixe la langue
 * (?lang=, sinon Accept-Language, sinon français). Renvoie du JSON en cas d'erreur.
 */
class ResolveApiConsulate
{
    public function __construct(private ConsulateContext $context)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $consulate = Consulate::query()
            ->where('slug', (string) $request->route('consulate'))
            ->where('is_active', true)
            ->first();

        if ($consulate === null) {
            return response()->json(['message' => 'Consulat introuvable.'], 404);
        }

        $this->context->set($consulate);

        // Retire le paramètre {consulate} : les contrôleurs reçoivent ainsi leurs propres
        // paramètres ({slug}, {reference}) par position, sans collision.
        $request->route()->forgetParameter('consulate');

        $locale = $this->resolveLocale($request, $consulate);
        SetCurrentConsulate::applyLocale($locale);
        $request->attributes->set('locale', $locale);

        return $next($request);
    }

    private function resolveLocale(Request $request, Consulate $consulate): string
    {
        $available = $consulate->availableLocales();

        // Sans en-tête Accept-Language, Symfony renvoie « en » par défaut : on ne s'y fie pas.
        $preferred = $request->headers->has('Accept-Language')
            ? $request->getPreferredLanguage(['fr', 'en', 'ar'])
            : null;

        $candidates = [$request->query('lang'), $preferred];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && in_array(substr($candidate, 0, 2), $available, true)) {
                return substr($candidate, 0, 2);
            }
        }

        return in_array('fr', $available, true) ? 'fr' : $available[0];
    }
}

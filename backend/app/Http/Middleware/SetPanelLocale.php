<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Langue de l'administration : préférence enregistrée de l'utilisateur connecté,
 * sinon choix de la session (écran de connexion), sinon français.
 * Filament passe automatiquement en droite à gauche pour l'arabe.
 */
class SetPanelLocale
{
    public const LOCALES = ['fr', 'en', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale ?: $request->session()->get('admin_locale', 'fr');

        app()->setLocale(in_array($locale, self::LOCALES, true) ? $locale : 'fr');

        return $next($request);
    }
}

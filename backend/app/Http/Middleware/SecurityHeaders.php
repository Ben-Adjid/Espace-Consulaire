<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** En-têtes de sécurité ajoutés à toutes les réponses web et API. Ne révèle ni la version de PHP ni le serveur. */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Ne pas annoncer la technologie (version de PHP) : utile aux attaquants qui ciblent les versions connues.
        header_remove('X-Powered-By');
        $response->headers->remove('X-Powered-By');

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        return $response;
    }
}

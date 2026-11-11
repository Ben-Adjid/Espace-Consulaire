<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Services\ConsulateContext;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;

/** Formulaire de contact : consentement, anti-spam (honeypot, délai minimal), rate limiting. */
class ContactController extends Controller
{
    private const MIN_FILL_SECONDS = 3;

    /** Jeton horodaté chiffré, à demander avant l'affichage du formulaire. */
    public function token(): JsonResponse
    {
        return response()->json(['token' => Crypt::encryptString((string) time())]);
    }

    public function store(ContactRequest $request, ConsulateContext $context): JsonResponse
    {
        $key = 'contact:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Trop de tentatives. Réessayez dans une minute.'], 429);
        }
        RateLimiter::hit($key, 60);

        // Robot : on répond « envoyé » sans rien enregistrer.
        if (filled($request->input('website')) || $this->submittedTooFast($request->input('_t'))) {
            return response()->json(['message' => 'Message envoyé.'], 201);
        }

        $context->require()->contactMessages()->create([
            ...$request->safe()->only(['name', 'email', 'phone', 'subject', 'message']),
            'ip_address' => $request->ip(),
            'consent_at' => now(),
        ]);

        return response()->json(['message' => 'Message envoyé.'], 201);
    }

    private function submittedTooFast(?string $token): bool
    {
        if (! $token) {
            return true;
        }

        try {
            $shownAt = (int) Crypt::decryptString($token);
        } catch (DecryptException) {
            return true;
        }

        return (time() - $shownAt) < self::MIN_FILL_SECONDS;
    }
}

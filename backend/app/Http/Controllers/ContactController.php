<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Services\ConsulateContext;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class ContactController extends Controller
{
    /** Délai minimal (secondes) entre l'affichage et l'envoi : en dessous, c'est un robot. */
    private const MIN_FILL_SECONDS = 3;

    public function show(ConsulateContext $context)
    {
        return view('contact', [
            // Horodatage chiffré : impossible à falsifier par un robot.
            'timestamp' => Crypt::encryptString((string) time()),
        ]);
    }

    public function store(ContactRequest $request, ConsulateContext $context)
    {
        $consulate = $context->require();

        // Anti-spam silencieux : honeypot rempli ou formulaire envoyé trop vite
        // => on feint le succès sans rien enregistrer.
        if (filled($request->input('website')) || $this->submittedTooFast($request->input('_t'))) {
            return redirect()->route('contact')->with('success', true);
        }

        $consulate->contactMessages()->create([
            ...$request->safe()->only(['name', 'email', 'phone', 'subject', 'message']),
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('contact')->with('success', true);
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

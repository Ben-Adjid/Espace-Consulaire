<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RequestDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Téléchargement d'une pièce par le personnel : même consulat, permission requise, consulté à chaque fois (journalisé).
 */
class PreRequestDocumentController extends Controller
{
    public function __invoke(Request $request, RequestDocument $document): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || ($user->can('decide_requests') && $user->consulate_id)), 403);

        $pre = $document->preRequest()->withoutGlobalScopes()->firstOrFail();
        abort_unless($user->isSuperAdmin() || (int) $user->consulate_id === (int) $pre->consulate_id, 403);

        $disk = Storage::disk(RequestDocument::DISK);
        abort_unless($disk->exists($document->stored_path), 404);

        \App\Models\DataAccessLog::record($user, 'RequestDocument', (string) $document->id, 'download', (int) $pre->consulate_id, $request->ip());

        return $disk->download($document->stored_path, $document->original_name, [
            'Content-Type' => $document->mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}

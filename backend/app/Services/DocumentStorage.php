<?php

namespace App\Services;

use App\Models\PreRequest;
use App\Models\RequestDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Téléversement des pièces : type vérifié par le CONTENU (pas seulement l'extension), taille limitée,
 * nom aléatoire, empreinte SHA-256. Les fichiers restent hors du web.
 */
class DocumentStorage
{
    public function store(PreRequest $request, UploadedFile $file, string $label): RequestDocument
    {
        if (! $file->isValid() || $file->getSize() > RequestDocument::MAX_KB * 1024) {
            throw new InvalidArgumentException('Fichier invalide ou trop volumineux (5 Mo maximum).');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
        if (! in_array($mime, RequestDocument::ALLOWED_MIME, true)) {
            throw new InvalidArgumentException('Format non accepté : PDF, JPEG ou PNG uniquement.');
        }

        $path = 'pre-requests/'.$request->id.'/'.Str::random(40).'.'.($mime === 'application/pdf' ? 'pdf' : ($mime === 'image/png' ? 'png' : 'jpg'));
        Storage::disk(RequestDocument::DISK)->put($path, file_get_contents($file->getRealPath()));

        return RequestDocument::create([
            'pre_request_id' => $request->id,
            'label' => mb_substr($label, 0, 160),
            'stored_path' => $path,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'mime' => $mime,
            'size' => $file->getSize(),
            'sha256' => hash_file('sha256', $file->getRealPath()),
            'status' => 'pending',
        ]);
    }

    /** Vérifie que le fichier stocké n'a pas été altéré depuis le téléversement. */
    public function isIntact(RequestDocument $doc): bool
    {
        $disk = Storage::disk(RequestDocument::DISK);

        return $disk->exists($doc->stored_path) && hash('sha256', $disk->get($doc->stored_path)) === $doc->sha256;
    }
}

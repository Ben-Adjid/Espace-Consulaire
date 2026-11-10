<?php

namespace App\Services;

use App\Models\Consulate;

/**
 * Consulat « courant » de la requête publique (singleton du conteneur).
 * Alimenté par le middleware SetCurrentConsulate.
 */
class ConsulateContext
{
    private ?Consulate $consulate = null;

    public function set(Consulate $consulate): void
    {
        $this->consulate = $consulate;
    }

    public function get(): ?Consulate
    {
        return $this->consulate;
    }

    public function require(): Consulate
    {
        return $this->consulate ?? abort(404);
    }
}

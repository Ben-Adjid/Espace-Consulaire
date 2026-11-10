<?php

namespace App\Contracts;

/**
 * V2 — pré-demande en ligne avec upload de documents (non implémenté dans la démo).
 * Contrat prévu pour découpler le futur module de la base de données.
 */
interface AppointmentPrerequestRepository
{
    public function create(int $consulateId, array $data): int;
}

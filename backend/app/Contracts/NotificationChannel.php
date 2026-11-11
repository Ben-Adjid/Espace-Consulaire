<?php

namespace App\Contracts;

/** V2 — notifications SMS / WhatsApp (non implémenté ; l'email est déjà géré par Mail). */
interface NotificationChannel
{
    public function send(string $to, string $message): void;
}

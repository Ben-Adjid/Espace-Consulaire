<?php

namespace App\Contracts;

/** V2 — paiements mobile money (non implémenté). */
interface PaymentGateway
{
    public function requestPayment(string $reference, int $amountFcfa, string $phone): string;
}

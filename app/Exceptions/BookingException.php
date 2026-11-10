<?php

namespace App\Exceptions;

use RuntimeException;

/** Échec métier d'une réservation. `translationKey` est la clé de message affichée au visiteur. */
class BookingException extends RuntimeException
{
    public function __construct(public readonly string $translationKey)
    {
        parent::__construct($translationKey);
    }

    public static function slotFull(): self
    {
        return new self('Ce créneau vient d’être complété. Veuillez en choisir un autre.');
    }

    public static function duplicate(): self
    {
        return new self('Vous avez déjà un rendez-vous sur ce créneau avec cette adresse email.');
    }

    public static function invalid(): self
    {
        return new self('Ce créneau n’est pas disponible pour le service ou la date choisis.');
    }
}

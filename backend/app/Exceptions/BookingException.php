<?php

namespace App\Exceptions;

use RuntimeException;

/** Échec métier d'une réservation. `translationKey` est le message (clé de traduction) renvoyé au visiteur. */
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

    public static function consentRequired(): self
    {
        return new self('Veuillez accepter le traitement de vos données pour continuer.');
    }

    public static function notCancellable(): self
    {
        return new self('Ce rendez-vous ne peut plus être annulé en ligne. Contactez le consulat.');
    }
}

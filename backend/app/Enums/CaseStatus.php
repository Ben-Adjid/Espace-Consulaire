<?php

namespace App\Enums;

/**
 * Étapes du dossier après le dépôt au guichet, jusqu'à la remise du document.
 * Production et expédition : pour les documents fabriqués (passeport, carte consulaire) envoyés par voie postale ou diplomatique.
 */
enum CaseStatus: string
{
    case Submitted = 'submitted';
    case Processing = 'processing';
    case InProduction = 'in_production';
    case Ready = 'ready';
    case Shipped = 'shipped';
    case Collected = 'collected';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => __('Dossier déposé'),
            self::Processing => __('En traitement'),
            self::InProduction => __('En production'),
            self::Ready => __('Prêt à retirer'),
            self::Shipped => __('Expédié'),
            self::Collected => __('Retiré'),
            self::Rejected => __('Rejeté'),
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}

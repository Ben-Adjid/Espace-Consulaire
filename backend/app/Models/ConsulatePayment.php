<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Paiement d'abonnement reçu d'un consulat (enregistré par le super admin).
 * À l'enregistrement, la date de fin d'abonnement du consulat est prolongée ;
 * un consulat suspendu pour non-paiement est réactivé.
 */
class ConsulatePayment extends Model
{
    public const METHODS = [
        'virement' => 'Virement bancaire',
        'especes' => 'Espèces',
        'cheque' => 'Chèque',
        'mobile_money' => 'Mobile money',
        'autre' => 'Autre',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (ConsulatePayment $payment) {
            $consulate = Consulate::withoutGlobalScopes()->find($payment->consulate_id);
            if (! $consulate) {
                return;
            }

            $until = $consulate->subscription_until;
            $newUntil = $until && $until->greaterThan($payment->period_end) ? $until : $payment->period_end;

            $changes = ['subscription_until' => $newUntil];
            // Réactivation uniquement si la suspension venait du non-paiement (pas d'une désactivation manuelle).
            // Un consulat créé par l'assistant s'active à la main par le super admin, après le premier paiement.
            if ($consulate->suspended_for_nonpayment) {
                $changes['is_active'] = true;
                $changes['suspended_for_nonpayment'] = false;
            }

            $consulate->forceFill($changes)->save();

            // Renouvellement enregistré : tous les appareils de confiance du consulat sont oubliés.
            // Le personnel et le consul doivent de nouveau saisir le code à 6 chiffres.
            \App\Services\TrustedDevices::forgetForConsulate($consulate->id);
        });
    }

    public function consulate(): BelongsTo
    {
        return $this->belongsTo(Consulate::class)->withoutGlobalScopes();
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by')->withoutGlobalScopes();
    }
}

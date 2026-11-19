<?php

namespace App\Actions;

use App\Models\Consulate;
use Illuminate\Validation\ValidationException;

/**
 * Active un consulat déjà abonné (au moins un paiement enregistré), en levant une suspension pour non-paiement.
 * Refuse un consulat sans abonnement : il faut d'abord enregistrer le premier paiement.
 */
class ActivateConsulate
{
    public function __invoke(Consulate $consulate): void
    {
        if ($consulate->subscription_until === null) {
            throw ValidationException::withMessages([
                'subscription' => __('Aucun abonnement enregistré : enregistrez d’abord le paiement.'),
            ]);
        }

        $consulate->forceFill(['is_active' => true, 'suspended_for_nonpayment' => false])->save();
    }
}

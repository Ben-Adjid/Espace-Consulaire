<?php

// Abonnement annuel des consulats à la plateforme.
return [
    // Jours de tolérance après la date de fin, avant la suspension automatique.
    'grace_days' => (int) env('SUBSCRIPTION_GRACE_DAYS', 30),

    // Jours avant la fin à partir desquels l'abonnement est signalé « à renouveler ».
    'warning_days' => (int) env('SUBSCRIPTION_WARNING_DAYS', 30),

    // Montant annuel proposé par défaut dans le formulaire (modifiable à chaque paiement).
    'default_amount' => (float) env('SUBSCRIPTION_DEFAULT_AMOUNT', 800000),
    'default_currency' => env('SUBSCRIPTION_DEFAULT_CURRENCY', 'XOF'),
];

<?php

return [
    // Délai minimal avant le premier jour réservable (0 = aujourd'hui possible).
    'min_lead_days' => (int) env('BOOKING_MIN_LEAD_DAYS', 1),

    // Nombre de jours à partir d'aujourd'hui pendant lesquels on peut réserver.
    'horizon_days' => (int) env('BOOKING_HORIZON_DAYS', 45),

    // Annulation par le visiteur possible jusqu'à N heures avant le rendez-vous.
    'cancel_min_hours' => (int) env('BOOKING_CANCEL_MIN_HOURS', 24),

    // Durées de conservation (RGPD) en jours, appliquées par `consulat:purge-personal-data`.
    'retention_days' => (int) env('DATA_RETENTION_DAYS', 365),
];

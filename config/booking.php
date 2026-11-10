<?php

return [
    // Délai minimal avant le premier jour réservable (0 = aujourd'hui possible).
    'min_lead_days' => (int) env('BOOKING_MIN_LEAD_DAYS', 1),

    // Nombre de jours à partir d'aujourd'hui pendant lesquels on peut réserver.
    'horizon_days' => (int) env('BOOKING_HORIZON_DAYS', 45),
];

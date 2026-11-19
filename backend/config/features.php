<?php

/*
|--------------------------------------------------------------------------
| Modules futurs (V2) — NON implémentés
|--------------------------------------------------------------------------
| Ces drapeaux réservent la place de modules encore absents (voir docs/ROADMAP_V2.md et app/Contracts/).
| Les pré-demandes avec pièces et le suivi de dossier sont livrés et toujours actifs : ils n'ont plus de drapeau.
*/

return [
    'mobile_money' => env('FEATURE_MOBILE_MONEY', false),        // paiements mobile money
    'sms_whatsapp' => env('FEATURE_SMS_WHATSAPP', false),        // notifications SMS / WhatsApp
    'consular_registration' => env('FEATURE_REGISTRATION', false), // immatriculation consulaire
    'emergency_alerts' => env('FEATURE_ALERTS', false),          // alertes d'urgence
];

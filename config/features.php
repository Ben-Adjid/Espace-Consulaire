<?php

/*
|--------------------------------------------------------------------------
| Fonctionnalités V2 (structure prévue, NON implémentées dans la démo)
|--------------------------------------------------------------------------
| Voir docs/ROADMAP_V2.md et app/Contracts/. Chaque drapeau sera activé
| lorsque son module sera livré.
*/

return [
    'online_prerequest' => env('FEATURE_PREREQUEST', false),     // pré-demande + upload de documents
    'case_tracking' => env('FEATURE_CASE_TRACKING', false),      // suivi de dossier
    'mobile_money' => env('FEATURE_MOBILE_MONEY', false),        // paiements mobile money
    'sms_whatsapp' => env('FEATURE_SMS_WHATSAPP', false),        // notifications SMS / WhatsApp
    'consular_registration' => env('FEATURE_REGISTRATION', false), // immatriculation consulaire
    'emergency_alerts' => env('FEATURE_ALERTS', false),          // alertes d'urgence
];

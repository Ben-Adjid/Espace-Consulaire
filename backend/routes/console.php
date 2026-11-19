<?php

use Illuminate\Support\Facades\Schedule;

// Rappels de rendez-vous la veille, à 08h00 (heure du serveur, Dakar = UTC).
Schedule::command('consulat:send-reminders')->dailyAt('08:00');

// Alerte de délai : pré-demandes en retard, un email par agent et par jour au plus.
Schedule::command('consulat:alert-overdue')->dailyAt('08:15');

// Rappel de fin de mandat aux membres du bureau concernés, un email par jour au plus.
Schedule::command('consulat:remind-mandates')->dailyAt('08:30');

// Purge RGPD des données personnelles au-delà de la durée de conservation, chaque nuit.
Schedule::command('consulat:purge-personal-data')->dailyAt('03:00');

// Abonnement annuel : suspension des consulats échus depuis plus que la période de grâce.
Schedule::command('consulat:check-subscriptions')->dailyAt('02:00');
Schedule::command('consulat:end-expired-memberships')->dailyAt('02:45');

// Scrutins en ligne : électorat, dépouillement, second tour et proclamation automatiques, toutes les 5 minutes.
Schedule::command('consulat:run-elections')->everyFiveMinutes();
Schedule::command('consulat:send-subscription-reminders')->dailyAt('08:05');
Schedule::command('consulat:backup')->dailyAt('01:30');

Schedule::command('consulat:send-validity-reminders')->dailyAt('08:10');

// Appareils de confiance : suppression des mémorisations échues, chaque nuit.
Schedule::call(fn () => \App\Services\TrustedDevices::purgeExpired())->dailyAt('04:00');

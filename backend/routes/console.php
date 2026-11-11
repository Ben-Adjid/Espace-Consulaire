<?php

use Illuminate\Support\Facades\Schedule;

// Rappels de rendez-vous la veille, à 08h00 (heure du serveur, Dakar = UTC).
Schedule::command('consulat:send-reminders')->dailyAt('08:00');

// Purge RGPD des données personnelles au-delà de la durée de conservation, chaque nuit.
Schedule::command('consulat:purge-personal-data')->dailyAt('03:00');

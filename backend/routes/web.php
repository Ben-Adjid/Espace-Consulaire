<?php

use Illuminate\Support\Facades\Route;

// Le site public est désormais le frontend Next.js (dossier /frontend).
// Ce backend ne sert que l'API (/api/v1) et l'administration Filament (/admin).
Route::redirect('/', '/admin')->name('platform');

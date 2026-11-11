<?php

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\ContentController;
use App\Http\Middleware\ResolveApiConsulate;
use Illuminate\Support\Facades\Route;

/*
| API publique v1 — consommée par le frontend Next.js (dossier /frontend).
| Chaque route est scopée par consulat : /api/v1/{consulate}/…
| Les réponses sont en JSON ; la langue vient de ?lang= ou de Accept-Language.
*/
Route::prefix('v1')->group(function () {
    Route::get('/consulates', function () {
        return \App\Http\Resources\ConsulateResource::collection(
            \App\Models\Consulate::query()->where('is_active', true)->orderBy('slug')->get()
        );
    });

    Route::prefix('{consulate}')
        ->where(['consulate' => '[a-z0-9][a-z0-9-]*'])
        ->middleware(ResolveApiConsulate::class)
        ->group(function () {
            Route::get('/', [ContentController::class, 'consulate']);
            Route::get('/services', [ContentController::class, 'services']);
            Route::get('/services/{slug}', [ContentController::class, 'service']);
            Route::get('/news', [ContentController::class, 'news']);
            Route::get('/news/{slug}', [ContentController::class, 'newsItem']);
            Route::get('/faqs', [ContentController::class, 'faqs']);
            Route::get('/pages/{slug}', [ContentController::class, 'page']);
            Route::get('/search', [ContentController::class, 'search']);

            Route::get('/availability/dates', [AppointmentController::class, 'dates']);
            Route::get('/availability/slots', [AppointmentController::class, 'slots']);
            Route::post('/appointments', [AppointmentController::class, 'store'])->middleware('throttle:30,1');
            Route::get('/appointments/{reference}', [AppointmentController::class, 'show']);
            Route::post('/appointments/{reference}/cancel', [AppointmentController::class, 'cancel'])->middleware('throttle:10,1');
            Route::get('/appointments/{reference}/ics', [AppointmentController::class, 'ics']);

            Route::get('/contact/token', [ContactController::class, 'token']);
            Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact');
        });
});

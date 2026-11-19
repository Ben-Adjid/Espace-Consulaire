<?php

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\CitizenController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\DependentController;
use App\Http\Controllers\Api\V1\PreRequestController;
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
            Route::get('/association', [\App\Http\Controllers\Api\V1\AssociationController::class, 'show']);
            // Export public (CSV) : registre financier approuvé, activités publiées, résultats publiés. Sans aucun nom d'adhérent.
            Route::get('/association/export.csv', \App\Http\Controllers\Api\V1\AssociationExportController::class);
            // Registre financier publié : solde et dernières écritures, sans aucun nom.
            Route::get('/association/ledger', [\App\Http\Controllers\Api\V1\AssociationLedgerController::class, 'show']);
            // Résultats d'un scrutin publiés (après proclamation), avec la vérification du journal des votes.
            Route::get('/association/elections/{id}/results', [\App\Http\Controllers\Api\V1\AssociationController::class, 'results'])->whereNumber('id');

            Route::get('/availability/dates', [AppointmentController::class, 'dates']);
            Route::get('/availability/slots', [AppointmentController::class, 'slots']);
            Route::post('/appointments', [AppointmentController::class, 'store'])->middleware('throttle:booking');
            Route::get('/appointments/{reference}', [AppointmentController::class, 'show']);
            Route::post('/appointments/{reference}/cancel', [AppointmentController::class, 'cancel'])->middleware('throttle:10,1');
            Route::post('/appointments/{reference}/reschedule', [AppointmentController::class, 'reschedule'])->middleware('throttle:10,1');
            Route::get('/appointments/{reference}/ics', [AppointmentController::class, 'ics']);

            Route::prefix('citizen')->group(function () {
                Route::post('/register', [CitizenController::class, 'register'])->middleware('throttle:10,1');
                // Connexion : 20 tentatives par minute et par adresse (un même réseau peut compter plusieurs personnes).
                Route::post('/login', [CitizenController::class, 'login'])->middleware('throttle:20,1');
                Route::patch('/profile', [CitizenController::class, 'updateProfile'])->middleware('throttle:10,1');
                Route::post('/email/confirm', [CitizenController::class, 'confirmEmailChange'])->middleware('throttle:10,1');
                Route::post('/logout', [CitizenController::class, 'logout']);
                Route::post('/verify-email', [CitizenController::class, 'verifyEmail'])->middleware('throttle:10,1');
                Route::post('/resend-verification', [CitizenController::class, 'resendVerification'])->middleware('throttle:5,1');
                Route::post('/forgot-password', [CitizenController::class, 'forgotPassword'])->middleware('throttle:5,1');
                Route::post('/reset-password', [CitizenController::class, 'resetPassword'])->middleware('throttle:10,1');
                Route::get('/me', [CitizenController::class, 'me']);
                Route::get('/appointments', [CitizenController::class, 'appointments']);
                Route::get('/timeline', \App\Http\Controllers\Api\V1\CitizenTimelineController::class);
                Route::get('/reports', [CitizenController::class, 'reports']);
                Route::post('/reports', [CitizenController::class, 'storeReport'])->middleware('throttle:5,1');
                // Pré-demande en ligne et pièces (téléversement privé)
                Route::get('/requests', [PreRequestController::class, 'index']);
                Route::post('/requests', [PreRequestController::class, 'store'])->middleware('throttle:20,1');
                Route::get('/requests/{id}', [PreRequestController::class, 'show'])->whereNumber('id');
                Route::put('/requests/{id}', [PreRequestController::class, 'update'])->whereNumber('id');
                Route::post('/requests/{id}/documents', [PreRequestController::class, 'upload'])->whereNumber('id')->middleware('throttle:30,1');
                Route::delete('/requests/{id}/documents/{docId}', [PreRequestController::class, 'deleteDocument'])->whereNumber(['id', 'docId']);
                Route::post('/requests/{id}/submit', [PreRequestController::class, 'submit'])->whereNumber('id');
                // Élections en ligne de l'association (candidature et vote des membres actifs)
                Route::get('/association-elections', [\App\Http\Controllers\Api\V1\AssociationElectionController::class, 'index']);
                Route::post('/association-elections/{id}/candidacy', [\App\Http\Controllers\Api\V1\AssociationElectionController::class, 'candidacy'])->whereNumber('id')->middleware('throttle:10,1');
                Route::delete('/association-elections/{id}/candidacy', [\App\Http\Controllers\Api\V1\AssociationElectionController::class, 'withdraw'])->whereNumber('id');
                // Registre financier : cotisations (trésorier), dépenses (proposées, puis approuvées par un autre membre du bureau).
                // Espace du bureau : tableau de bord, activités publiées, informations publiques (selon la fonction).
                Route::get('/association/board', [\App\Http\Controllers\Api\V1\AssociationBoardController::class, 'dashboard']);
                Route::post('/association/activities', [\App\Http\Controllers\Api\V1\AssociationBoardController::class, 'publishActivity']);
                Route::patch('/association/info', [\App\Http\Controllers\Api\V1\AssociationBoardController::class, 'updatePublicInfo']);
                Route::get('/association/members', \App\Http\Controllers\Api\V1\AssociationMembersController::class);
                Route::get('/association/members/export.csv', [\App\Http\Controllers\Api\V1\AssociationMembersController::class, 'export']);
                // Gestion des adhérents : président seul.
                Route::post('/association/members', [\App\Http\Controllers\Api\V1\AssociationMemberManagementController::class, 'store']);
                Route::patch('/association/members/{id}', [\App\Http\Controllers\Api\V1\AssociationMemberManagementController::class, 'update'])->whereNumber('id');
                Route::get('/association/journal', [\App\Http\Controllers\Api\V1\AssociationBoardController::class, 'journal']);
                Route::get('/association/ledger', [\App\Http\Controllers\Api\V1\AssociationLedgerController::class, 'mine']);
                Route::post('/association/ledger/contribution',[\App\Http\Controllers\Api\V1\AssociationLedgerController::class, 'contribution'])->middleware('throttle:20,1');
                Route::post('/association/ledger/expense', [\App\Http\Controllers\Api\V1\AssociationLedgerController::class, 'proposeExpense'])->middleware('throttle:20,1');
                Route::post('/association/ledger/{id}/approve', [\App\Http\Controllers\Api\V1\AssociationLedgerController::class, 'approve'])->whereNumber('id')->middleware('throttle:20,1');
                Route::post('/association-elections/{id}/attest',[\App\Http\Controllers\Api\V1\AssociationElectionController::class, 'attest'])->whereNumber('id')->middleware('throttle:10,1');
                Route::post('/association-elections/{id}/receipt',[\App\Http\Controllers\Api\V1\AssociationElectionController::class, 'verifyReceipt'])->whereNumber('id')->middleware('throttle:10,1');
                Route::post('/association-elections/{id}/vote', [\App\Http\Controllers\Api\V1\AssociationElectionController::class, 'vote'])->whereNumber('id')->middleware('throttle:10,1');
                // Messagerie citoyen ↔ consulat
                Route::get('/messages', [\App\Http\Controllers\Api\V1\ConversationController::class, 'index']);
                Route::post('/messages', [\App\Http\Controllers\Api\V1\ConversationController::class, 'store'])->middleware('throttle:5,1');
                Route::get('/messages/{id}', [\App\Http\Controllers\Api\V1\ConversationController::class, 'show'])->whereNumber('id');
                Route::post('/messages/{id}/reply', [\App\Http\Controllers\Api\V1\ConversationController::class, 'reply'])->whereNumber('id')->middleware('throttle:20,1');
                // Espace famille : personnes à charge
                Route::get('/dependents', [DependentController::class, 'index']);
                Route::post('/dependents', [DependentController::class, 'store'])->middleware('throttle:20,1');
                Route::delete('/dependents/{id}', [DependentController::class, 'destroy'])->whereNumber('id');
            });

            Route::get('/contact/token', [ContactController::class, 'token']);
            Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact');
        });
});

<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// Page d'accueil de la plateforme : liste des consulats actifs.
Route::get('/', [PlatformController::class, 'index'])->name('platform');

// Sitemap global (toutes les pages publiques de tous les consulats actifs).
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

/*
| Site public d'un consulat : /{consulate_slug}/…
| Le middleware `consulate` charge le consulat, la langue et le thème.
| Les segments réservés (admin, livewire…) ne sont jamais pris pour un slug.
*/
Route::prefix('{consulate}')
    ->where(['consulate' => '(?!(?:admin|livewire|storage|up|filament)(?![a-z0-9-]))[a-z0-9][a-z0-9-]*'])
    ->middleware('consulate')
    ->group(function () {
        Route::get('/', HomeController::class)->name('home');

        Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
        Route::get('/services/{slug}', [ServiceController::class, 'show'])->name('services.show');

        Route::get('/news', [NewsController::class, 'index'])->name('news.index');
        Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');

        Route::get('/faq', FaqController::class)->name('faq');

        Route::get('/contact', [ContactController::class, 'show'])->name('contact');
        Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');

        Route::get('/appointment', [AppointmentController::class, 'create'])->name('appointment.create');
        Route::get('/appointment/confirmation/{reference}', [AppointmentController::class, 'confirmation'])
            ->middleware('throttle:30,1')->name('appointment.confirmation');

        Route::get('/pages/{slug}', PageController::class)->name('pages.show');
    });

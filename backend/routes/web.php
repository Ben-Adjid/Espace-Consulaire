<?php

use App\Services\MonthlyReport;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Le site public est désormais le frontend Next.js (dossier /frontend).
// Ce backend ne sert que l'API (/api/v1) et l'administration Filament (/admin).
Route::redirect('/', '/admin')->name('platform');

// Export CSV du rapport mensuel : réservé au personnel connecté, limité à son consulat.
Route::get('/admin/rapport-mensuel/export', function (Request $request, MonthlyReport $reports) {
    $user = $request->user();
    // Super admin : tous les consulats. Personnel : uniquement son propre consulat, avec la permission.
    abort_unless($user && ($user->isSuperAdmin() || ($user->can('manage_appointments') && $user->consulate_id)), 403);

    $month = CarbonImmutable::createFromFormat('Y-m', (string) $request->query('month', now()->format('Y-m')));
    $consulate = $user->isSuperAdmin()
        ? \App\Models\Consulate::query()->findOrFail((int) $request->query('consulate_id'))
        : $user->consulate;
    abort_unless($consulate, 404);

    $csv = $reports->toCsv($reports->build($consulate, $month));
    $name = 'rapport-'.$consulate->slug.'-'.$month->format('Y-m').'.csv';

    return response($csv, 200, [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="'.$name.'"',
    ]);
})->middleware(['web', 'auth'])->name('admin.rapport-mensuel.export');

// Rapport mensuel imprimable (enregistrable en PDF depuis le navigateur), dans la langue de l'utilisateur.
// Mêmes règles d'accès que l'export CSV : le personnel ne voit que son consulat.
Route::get('/admin/rapport-mensuel/imprimer', function (Request $request, MonthlyReport $reports) {
    $user = $request->user();
    abort_unless($user && ($user->isSuperAdmin() || ($user->can('manage_appointments') && $user->consulate_id)), 403);

    $month = CarbonImmutable::createFromFormat('Y-m', (string) $request->query('month', now()->format('Y-m')));
    $consulate = $user->isSuperAdmin()
        ? \App\Models\Consulate::query()->findOrFail((int) $request->query('consulate_id'))
        : $user->consulate;
    abort_unless($consulate, 404);

    return view('admin.monthly-report-print', [
        'consulate' => $consulate,
        'month' => $month,
        'report' => $reports->build($consulate, $month),
        'cases' => $reports->caseStats($consulate, $month),
        'user' => $user,
    ]);
})->middleware(['web', 'auth', \App\Http\Middleware\SetPanelLocale::class])->name('admin.rapport-mensuel.print');

// Téléchargement d'une pièce d'une pré-demande : personnel du consulat uniquement.
Route::get('/admin/pre-requests/documents/{document}', \App\Http\Controllers\Admin\PreRequestDocumentController::class)
    ->middleware(['web', 'auth'])
    ->name('admin.pre-requests.document');

// Reçu d'un paiement d'abonnement : le super admin voit tout ; le consul ne voit que les reçus de SON consulat.
Route::get('/admin/paiements/{payment}/recu', function (\App\Models\ConsulatePayment $payment) {
    $user = request()->user();
    $ownConsul = $user?->hasRole(\App\Models\User::ROLE_CONSULATE_ADMIN)
        && (int) $user->consulate_id === (int) $payment->consulate_id;
    abort_unless($user && ($user->isSuperAdmin() || $ownConsul), 403);

    return view('admin.subscription-receipt', [
        'payment' => $payment->load('recorder'),
        'consulate' => $payment->consulate,
    ]);
})->middleware(['web', 'auth'])->name('admin.paiements.recu');

// Langue de l'administration : enregistrée pour l'utilisateur connecté (conservée après reconnexion) et en session.
Route::get('/admin/langue/{locale}', function (Request $request, string $locale) {
    abort_unless(in_array($locale, \App\Http\Middleware\SetPanelLocale::LOCALES, true), 404);
    $request->session()->put('admin_locale', $locale);
    $request->user()?->forceFill(['locale' => $locale])->save();

    return redirect()->back(fallback: '/admin');
})->middleware('web')->name('admin.locale');

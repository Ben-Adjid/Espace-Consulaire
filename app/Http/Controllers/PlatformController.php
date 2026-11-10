<?php

namespace App\Http\Controllers;

use App\Models\Consulate;

/** Page d'accueil de la plateforme : choix du consulat. */
class PlatformController extends Controller
{
    public function index()
    {
        return view('platform', [
            'consulates' => Consulate::query()->where('is_active', true)->orderBy('slug')->get(),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\ConsulateContext;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    /** Page de prise de rendez-vous (le formulaire est un composant Livewire). */
    public function create(Request $request, ConsulateContext $context)
    {
        $consulate = $context->require();

        return view('appointment.create', [
            'bookableServices' => $consulate->services()->active()->ordered()->where('allow_appointment', true)->get(),
            'preselected' => (string) $request->query('service', ''),
        ]);
    }

    /** Confirmation affichée après la réservation (la référence sert de jeton non devinable). */
    public function confirmation(ConsulateContext $context, string $reference)
    {
        $appointment = $context->require()->appointments()
            ->with('service')->where('reference', $reference)->firstOrFail();

        return view('appointment.confirmation', ['appointment' => $appointment]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\ConsulateContext;

class ServiceController extends Controller
{
    public function index(ConsulateContext $context)
    {
        $consulate = $context->require();

        return view('services.index', [
            'services' => $consulate->services()->active()->ordered()->get(),
        ]);
    }

    public function show(ConsulateContext $context, string $slug)
    {
        $consulate = $context->require();

        $service = $consulate->services()->active()->where('slug', $slug)->firstOrFail();

        return view('services.show', [
            'service' => $service,
            'others' => $consulate->services()->active()->ordered()->where('id', '!=', $service->id)->limit(4)->get(),
        ]);
    }
}

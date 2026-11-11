<?php

namespace App\Http\Controllers;

use App\Services\ConsulateContext;

class HomeController extends Controller
{
    public function __invoke(ConsulateContext $context)
    {
        $consulate = $context->require();

        return view('home', [
            'services' => $consulate->services()->active()->ordered()->get(),
            'news' => $consulate->news()->published()->limit(3)->get(),
        ]);
    }
}

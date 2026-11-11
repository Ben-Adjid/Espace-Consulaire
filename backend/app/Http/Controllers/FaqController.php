<?php

namespace App\Http\Controllers;

use App\Services\ConsulateContext;

class FaqController extends Controller
{
    public function __invoke(ConsulateContext $context)
    {
        return view('faq', ['faqs' => $context->require()->faqs()->published()->get()]);
    }
}

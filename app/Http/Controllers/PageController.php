<?php

namespace App\Http\Controllers;

use App\Services\ConsulateContext;

/** Pages « libres » (mentions légales, confidentialité, démo non officielle…). */
class PageController extends Controller
{
    public function __invoke(ConsulateContext $context, string $slug)
    {
        $page = $context->require()->pages()->where('is_published', true)->where('slug', $slug)->firstOrFail();

        return view('page', ['page' => $page]);
    }
}

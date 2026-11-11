<?php

namespace App\Http\Controllers;

use App\Services\ConsulateContext;

class NewsController extends Controller
{
    public function index(ConsulateContext $context)
    {
        $consulate = $context->require();

        return view('news.index', [
            'news' => $consulate->news()->published()->paginate(9)->withQueryString(),
        ]);
    }

    public function show(ConsulateContext $context, string $slug)
    {
        $consulate = $context->require();

        $item = $consulate->news()->published()->where('slug', $slug)->firstOrFail();

        return view('news.show', [
            'item' => $item,
            'others' => $consulate->news()->published()->where('id', '!=', $item->id)->limit(3)->get(),
        ]);
    }
}

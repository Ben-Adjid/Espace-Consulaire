<?php

namespace App\Http\Controllers;

use App\Models\Consulate;
use Illuminate\Support\Facades\URL;

/** sitemap.xml global + robots.txt. */
class SitemapController extends Controller
{
    public function __invoke()
    {
        $entries = [['loc' => url('/'), 'lastmod' => null]];

        Consulate::query()->where('is_active', true)->orderBy('slug')->get()->each(function (Consulate $c) use (&$entries) {
            URL::defaults(['consulate' => $c->slug]);

            foreach (['home', 'services.index', 'news.index', 'faq', 'contact', 'appointment.create'] as $name) {
                $entries[] = ['loc' => route($name), 'lastmod' => $c->updated_at];
            }
            foreach ($c->services()->active()->get() as $s) {
                $entries[] = ['loc' => route('services.show', $s->slug), 'lastmod' => $s->updated_at];
            }
            foreach ($c->news()->published()->get() as $n) {
                $entries[] = ['loc' => route('news.show', $n->slug), 'lastmod' => $n->updated_at];
            }
            foreach ($c->pages()->where('is_published', true)->get() as $p) {
                $entries[] = ['loc' => route('pages.show', $p->slug), 'lastmod' => $p->updated_at];
            }
        });

        return response()->view('sitemap', ['entries' => $entries])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots()
    {
        $body = "User-agent: *\nDisallow: /admin\nDisallow: /livewire\nAllow: /\n\nSitemap: ".route('sitemap')."\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConsulateResource;
use App\Http\Resources\FaqResource;
use App\Http\Resources\NewsResource;
use App\Http\Resources\PageResource;
use App\Http\Resources\ServiceResource;
use App\Models\Consulate;
use App\Models\Faq;
use App\Models\News;
use App\Models\Page;
use App\Models\Service;
use App\Services\ConsulateContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Contenus publics d'un consulat : fiche, services, actualités, FAQ, pages, recherche. */
class ContentController extends Controller
{
    public function consulate(ConsulateContext $context): ConsulateResource
    {
        return new ConsulateResource($context->require());
    }

    public function services(ConsulateContext $context): JsonResponse
    {
        $services = $context->require()->services()->active()->ordered()->get();

        return ServiceResource::collection($services)->response();
    }

    public function service(ConsulateContext $context, string $slug): ServiceResource
    {
        return new ServiceResource(
            $context->require()->services()->active()->where('slug', $slug)->firstOrFail()
        );
    }

    public function news(Request $request, ConsulateContext $context): JsonResponse
    {
        $paginator = $context->require()->news()->published()->paginate(
            perPage: min((int) $request->query('per_page', 9), 30)
        );

        return NewsResource::collection($paginator)->response();
    }

    public function newsItem(ConsulateContext $context, string $slug): NewsResource
    {
        return new NewsResource($context->require()->news()->published()->where('slug', $slug)->firstOrFail());
    }

    public function faqs(ConsulateContext $context): JsonResponse
    {
        return FaqResource::collection($context->require()->faqs()->published()->get())->response();
    }

    public function page(ConsulateContext $context, string $slug): PageResource
    {
        return new PageResource(
            $context->require()->pages()->where('is_published', true)->where('slug', $slug)->firstOrFail()
        );
    }

    /** Recherche simple dans services, actualités et FAQ (insensible à la casse). */
    public function search(Request $request, ConsulateContext $context): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $consulate = $context->require();
        $locale = app()->getLocale();
        $results = [];

        $consulate->services()->active()->get()
            ->filter(fn (Service $s) => str_contains(mb_strtolower($s->getTranslation('name', $locale).' '.$s->getTranslation('summary', $locale)), mb_strtolower($q)))
            ->each(function (Service $s) use (&$results) {
                $results[] = ['type' => 'service', 'title' => $s->name, 'slug' => $s->slug, 'excerpt' => $s->summary];
            });

        $consulate->news()->published()->get()
            ->filter(fn (News $n) => str_contains(mb_strtolower($n->getTranslation('title', $locale).' '.$n->getTranslation('excerpt', $locale)), mb_strtolower($q)))
            ->each(function (News $n) use (&$results) {
                $results[] = ['type' => 'news', 'title' => $n->title, 'slug' => $n->slug, 'excerpt' => $n->excerpt];
            });

        $consulate->faqs()->published()->get()
            ->filter(fn (Faq $f) => str_contains(mb_strtolower($f->getTranslation('question', $locale).' '.$f->getTranslation('answer', $locale)), mb_strtolower($q)))
            ->each(function (Faq $f) use (&$results) {
                $results[] = ['type' => 'faq', 'title' => $f->question, 'slug' => null, 'excerpt' => null];
            });

        return response()->json(['data' => array_slice($results, 0, 20)]);
    }
}

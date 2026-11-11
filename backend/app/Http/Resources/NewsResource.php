<?php

namespace App\Http\Resources;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin News */
class NewsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $n = $this->resource;

        return [
            'slug' => $n->slug,
            'title' => $n->title,
            'excerpt' => $n->excerpt,
            'body' => $n->body,
            'image_url' => $n->imageUrl(),
            'published_at' => $n->published_at?->toIso8601String(),
        ];
    }
}

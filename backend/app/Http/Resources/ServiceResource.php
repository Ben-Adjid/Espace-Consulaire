<?php

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Service */
class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $s = $this->resource;

        return [
            'slug' => $s->slug,
            'name' => $s->name,
            'summary' => $s->summary,
            'description' => $s->description,
            'documents' => $s->documentsList(),
            'fee' => $s->fee,
            'processing_time' => $s->processing_time,
            'icon' => $s->icon,
            'allow_appointment' => (bool) $s->allow_appointment,
        ];
    }
}

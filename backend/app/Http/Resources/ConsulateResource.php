<?php

namespace App\Http\Resources;

use App\Models\Consulate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Consulate */
class ConsulateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $c = $this->resource;

        return [
            'slug' => $c->slug,
            'name' => $c->name,
            'represented_country' => $c->represented_country,
            'host_country' => $c->host_country,
            'consul_name' => $c->consul_name,
            'hero_title' => $c->hero_title ?: $c->name,
            'hero_subtitle' => $c->hero_subtitle,
            'consul_message' => $c->consul_message,
            'mission' => $c->mission,
            'address' => $c->address,
            'opening_hours' => $c->opening_hours,
            'phone' => $c->phone,
            'email' => $c->email,
            'whatsapp' => $c->whatsappNumber(),
            'latitude' => $c->latitude,
            'longitude' => $c->longitude,
            'logo_url' => $c->logoUrl(),
            'initials' => $c->initials(),
            'languages' => $c->availableLocales(),
            'theme' => [
                'primary' => $this->safeColor($c->primary_color, '#1d4ed8'),
                'secondary' => $this->safeColor($c->secondary_color, '#facc15'),
                'accent' => $this->safeColor($c->accent_color, '#dc2626'),
            ],
        ];
    }

    private function safeColor(?string $value, string $default): string
    {
        return preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $value) ? $value : $default;
    }
}

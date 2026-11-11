<?php

namespace App\Models;

use App\Models\Scopes\ConsulateScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Consulate extends Model
{
    use HasFactory, HasTranslations;

    public const LOCALES = ['fr', 'en', 'ar'];

    protected $guarded = [];

    public array $translatable = [
        'name', 'represented_country', 'host_country', 'address', 'opening_hours',
        'consul_message', 'mission', 'hero_title', 'hero_subtitle',
    ];

    protected function casts(): array
    {
        return [
            'languages' => 'array',
            'is_active' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    protected static function booted(): void
    {
        // Un admin de consulat ne voit que son propre consulat dans Filament.
        static::addGlobalScope(new ConsulateScope('id'));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function news(): HasMany
    {
        return $this->hasMany(News::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function contactMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Langues réellement proposées par ce consulat (sous-ensemble de fr/en/ar). */
    public function availableLocales(): array
    {
        $locales = array_values(array_intersect($this->languages ?: ['fr'], self::LOCALES));

        return $locales ?: ['fr'];
    }

    public function logoUrl(): ?string
    {
        return $this->logo ? Storage::disk('public')->url($this->logo) : null;
    }

    /** Initiales (2 lettres) utilisées par le logo neutre généré. */
    public function initials(): string
    {
        return Str::upper(Str::substr(Str::ascii($this->getTranslation('represented_country', 'fr')), 0, 2));
    }

    /** Numéro WhatsApp nettoyé (chiffres uniquement) pour wa.me. */
    public function whatsappNumber(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->whatsapp);

        return $digits ?: null;
    }

    /** Variables CSS du thème du consulat (couleurs validées, jamais injectées brutes). */
    public function themeCss(): string
    {
        $safe = fn (?string $c, string $default) => preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $c) ? $c : $default;

        return sprintf(
            ':root{--brand:%s;--brand-2:%s;--brand-3:%s;}',
            $safe($this->primary_color, '#1d4ed8'),
            $safe($this->secondary_color, '#facc15'),
            $safe($this->accent_color, '#dc2626'),
        );
    }
}

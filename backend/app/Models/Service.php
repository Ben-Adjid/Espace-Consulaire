<?php

namespace App\Models;

use App\Models\Concerns\BelongsToConsulate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Service extends Model
{
    use BelongsToConsulate, HasFactory, HasTranslations;

    protected $guarded = [];

    public array $translatable = ['name', 'summary', 'description', 'required_documents', 'processing_time'];

    protected function casts(): array
    {
        return ['allow_appointment' => 'boolean', 'is_active' => 'boolean'];
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Liste des documents requis (un par ligne dans l'éditeur).
     * Expressions régulières en mode /u : sans lui, \R coupe les caractères arabes multi-octets.
     */
    public function documentsList(): array
    {
        $text = (string) $this->getTranslation('required_documents', app()->getLocale());

        $lines = preg_split('/\R/u', $text) ?: [];

        return array_values(array_filter(array_map(
            fn (string $line) => trim((string) preg_replace('/^[\-•*\s]+/u', '', $line)),
            $lines
        )));
    }
}

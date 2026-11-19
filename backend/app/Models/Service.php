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
        return ['allow_appointment' => 'boolean', 'is_active' => 'boolean', 'requires_validated_file' => 'boolean'];
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function fees(): HasMany
    {
        return $this->hasMany(ServiceFee::class)->orderByDesc('effective_from');
    }

    /** Tarif en vigueur à une date (le plus récent dont la date d'effet est atteinte). */
    public function feeOn(?\DateTimeInterface $date = null): ?ServiceFee
    {
        $day = \Carbon\CarbonImmutable::instance($date ?? now())->toDateString();

        return $this->fees()->withoutGlobalScopes()->whereDate('effective_from', '<=', $day)->first();
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
        return array_map(fn (array $item) => $item['label'], $this->documentItems());
    }

    /** Étiquettes de situation reconnues en début de ligne (français ou anglais). */
    public const DOCUMENT_TAGS = [
        'mineur' => 'minor', 'minor' => 'minor',
        'majeur' => 'adult', 'adult' => 'adult',
        'premiere' => 'first', 'première' => 'first', 'first' => 'first',
        'renouvellement' => 'renewal', 'renewal' => 'renewal',
    ];

    /**
     * Pièces avec leurs conditions. Une ligne peut commencer par des étiquettes : « [mineur] [renouvellement] … ».
     * Sans étiquette, la pièce concerne tout le monde.
     *
     * @return array<int, array{label: string, tags: array<int, string>}>
     */
    public function documentItems(?string $locale = null, bool $useFallback = true): array
    {
        $text = (string) $this->getTranslation('required_documents', $locale ?? app()->getLocale(), $useFallback);
        $lines = preg_split('/\R/u', $text) ?: [];
        $items = [];

        foreach ($lines as $line) {
            $line = trim((string) preg_replace('/^[\-•*\s]+/u', '', $line));
            $tags = [];
            while (preg_match('/^\[([^\]]+)\]\s*/u', $line, $m)) {
                $key = mb_strtolower(trim($m[1]));
                if (isset(self::DOCUMENT_TAGS[$key])) {
                    $tags[] = self::DOCUMENT_TAGS[$key];
                }
                $line = mb_substr($line, mb_strlen($m[0]));
            }
            if ($line !== '') {
                $items[] = ['label' => $line, 'tags' => array_values(array_unique($tags))];
            }
        }

        return $items;
    }
}

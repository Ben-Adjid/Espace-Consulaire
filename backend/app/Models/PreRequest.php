<?php

namespace App\Models;

use App\Models\Concerns\BelongsToConsulate;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Pré-demande : brouillon modifiable, puis soumise au consulat qui vérifie les pièces. */
class PreRequest extends Model
{
    use BelongsToConsulate;

    /**
     * Cas particuliers signalés par le consulat. Le consulat définit les pièces à fournir pour chaque cas ;
     * la plateforme ne les invente pas : elle signale le cas et le suit.
     */
    public const SPECIAL_CASES = [
        'lost_document' => 'Document perdu ou volé',
        'minor_without_parent' => 'Mineur sans parent présent',
        'proxy' => 'Procuration ou représentant',
        'urgent_travel' => 'Voyage urgent',
        'other' => 'Autre situation',
    ];

    public const STATUSES = [
        'draft' => 'Brouillon',
        'submitted' => 'Soumise',
        'returned' => 'À compléter',
        'validated' => 'Validée',
        'rejected' => 'Refusée',
    ];

    /** Types d'événements de l'historique. */
    public const EVENTS = [
        'submitted' => 'Pré-demande soumise',
        'assigned' => 'Dossier attribué',
        'reviewed' => 'Décision du consulat',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['data' => 'array', 'submitted_at' => 'datetime', 'reviewed_at' => 'datetime', 'assigned_at' => 'datetime', 'is_urgent' => 'boolean', 'valid_until' => 'date', 'validity_reminded_at' => 'datetime'];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(RequestDocument::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(PreRequestEvent::class)->oldest('id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withoutGlobalScopes();
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class)->withoutGlobalScopes();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** Modifiable tant que le dossier n'est pas soumis, ou renvoyé au citoyen pour complément. */
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'returned'], true);
    }

    /** Ajoute une ligne à l'historique du dossier. */
    public function recordEvent(string $type, ?string $note = null, ?User $user = null, string $actor = 'staff'): PreRequestEvent
    {
        return $this->events()->create([
            'type' => $type,
            'note' => $note,
            'user_id' => $user?->id,
            'actor' => $actor,
        ]);
    }

    /** Échéance de traitement : date de soumission + délai cible du service (null si aucun délai n'est fixé). */
    public function dueAt(): ?CarbonImmutable
    {
        $days = $this->service?->target_days;
        if (! $days || ! $this->submitted_at) {
            return null;
        }

        return CarbonImmutable::instance($this->submitted_at)->addDays($days);
    }

    /** En retard : encore en attente de décision au-delà de l'échéance. */
    public function isOverdue(): bool
    {
        $due = $this->dueAt();

        return $this->status === 'submitted' && $due !== null && $due->isPast();
    }

    /**
     * Pièces exigées par le service et pas encore déposées (hors pièces refusées).
     * Seules les pièces demandées à tous sont comparées ; celles qui dépendent d'une situation
     * (mineur, renouvellement…) sont rendues à part, sans être déclarées manquantes.
     *
     * @return array{missing: list<string>, conditional: list<string>}
     */
    public function documentCheck(): array
    {
        $norm = fn (string $s) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', $s)));
        $provided = $this->documents->where('status', '!=', 'rejected')->map(fn ($d) => $norm($d->label))->all();

        $items = $this->service?->documentItems() ?? [];

        // Le citoyen a pu nommer la pièce dans une autre langue que celle de l'affichage :
        // une ligne est fournie si la même ligne (même position) d'une autre traduction correspond.
        // Seules les traductions ayant le même nombre de lignes sont comparées, sinon l'alignement n'est pas fiable.
        $aligned = [];
        foreach (array_keys($this->service?->getTranslations('required_documents') ?? []) as $locale) {
            $other = $this->service->documentItems($locale, false);
            if (count($other) === count($items)) {
                $aligned[] = $other;
            }
        }

        $missing = [];
        $conditional = [];
        foreach ($items as $i => $item) {
            if ($item['tags']) {
                $conditional[] = $item['label'];

                continue;
            }
            $labels = array_merge([$item['label']], array_map(fn (array $other) => $other[$i]['label'], $aligned));
            $isProvided = collect($labels)->contains(fn (string $l) => in_array($norm($l), $provided, true));
            if (! $isProvided) {
                $missing[] = $item['label'];
            }
        }

        return ['missing' => $missing, 'conditional' => $conditional];
    }

    /** Identifiants des pré-demandes en retard (calcul en PHP : portable SQLite / MySQL). */
    public static function overdueIds(): array
    {
        return static::query()->where('status', 'submitted')->with('service')->get()
            ->filter(fn (self $p) => $p->isOverdue())->pluck('id')->all();
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToConsulate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Conversation entre un citoyen et son consulat (messagerie de l'espace citoyen). */
class Conversation extends Model
{
    use BelongsToConsulate;

    public const STATUSES = ['open' => 'Ouverte', 'closed' => 'Clôturée'];

    /** Nombre maximal de conversations ouvertes en même temps par citoyen (limite les envois abusifs). */
    public const MAX_OPEN_PER_CITIZEN = 5;

    protected $fillable = [
        'consulate_id', 'citizen_id', 'pre_request_id', 'subject', 'status',
        'unread_by_staff', 'unread_by_citizen', 'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'unread_by_staff' => 'boolean',
            'unread_by_citizen' => 'boolean',
            'last_message_at' => 'datetime',
        ];
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class)->withoutGlobalScopes();
    }

    public function preRequest(): BelongsTo
    {
        return $this->belongsTo(PreRequest::class)->withoutGlobalScopes();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class)->oldest('id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Message d'une conversation : envoyé par le citoyen ou par un membre du consulat. */
class ConversationMessage extends Model
{
    protected $fillable = ['conversation_id', 'sender', 'user_id', 'body'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

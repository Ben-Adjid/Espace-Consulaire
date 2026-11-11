<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'changed_fields' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id')->withoutGlobalScopes();
    }

    public function consulate(): BelongsTo
    {
        return $this->belongsTo(Consulate::class)->withoutGlobalScopes();
    }
}

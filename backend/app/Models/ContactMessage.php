<?php

namespace App\Models;

use App\Models\Concerns\BelongsToConsulate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use BelongsToConsulate, HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_read' => 'boolean'];
    }
}

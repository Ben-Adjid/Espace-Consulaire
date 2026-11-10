<?php

namespace App\Models;

use App\Models\Concerns\BelongsToConsulate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use BelongsToConsulate, HasFactory, HasTranslations;

    protected $guarded = [];

    public array $translatable = ['title', 'body'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }
}

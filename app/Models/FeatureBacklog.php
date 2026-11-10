<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Élément du backlog (table feature_backlog). Volontairement hors multi-consulats. */
class FeatureBacklog extends Model
{
    protected $table = 'feature_backlog';

    protected $guarded = [];
}

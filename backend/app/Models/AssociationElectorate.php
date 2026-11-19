<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Liste des électeurs figée à l'ouverture du vote. Un changement d'adhésion ensuite ne change pas qui peut voter. */
class AssociationElectorate extends Model
{
    public $timestamps = false;

    protected $table = 'association_electorate';

    protected $guarded = [];
}

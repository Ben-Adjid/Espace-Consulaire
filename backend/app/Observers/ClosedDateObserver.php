<?php

namespace App\Observers;

use App\Actions\NotifyClosure;
use App\Models\ClosedDate;

/** Une fermeture nouvellement enregistrée prévient les citoyens concernés. */
class ClosedDateObserver
{
    public function created(ClosedDate $closure): void
    {
        app(NotifyClosure::class)->execute($closure);
    }
}

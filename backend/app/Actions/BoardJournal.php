<?php

namespace App\Actions;

use App\Models\Association;
use App\Models\AssociationBoardLog;
use App\Models\Citizen;

/** Inscrit une action du bureau dans le journal (qui, quoi, quand). */
class BoardJournal
{
    public static function record(Association $association, ?Citizen $citizen, string $action, string $label): AssociationBoardLog
    {
        return AssociationBoardLog::create([
            'association_id' => $association->id,
            'citizen_id' => $citizen?->id,
            'action' => $action,
            'label' => mb_substr($label, 0, 255),
        ]);
    }
}

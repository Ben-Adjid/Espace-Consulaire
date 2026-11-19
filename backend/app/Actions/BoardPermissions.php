<?php

namespace App\Actions;

/**
 * Qui peut faire quoi dans le bureau. Une fonction n'a que les droits de sa mission.
 * Les finances (cotisations, dépenses) restent dans LedgerActions ; les élections dans leurs actions.
 */
class BoardPermissions
{
    /** Publier une activité : président, vice-président, responsable des activités. */
    public const PUBLISH_ACTIVITY = ['president', 'vice_president', 'sports'];

    /** Modifier les informations publiques : président, vice-président, responsable de la communication. */
    public const EDIT_PUBLIC_INFO = ['president', 'vice_president', 'communication'];

    /** Convoquer les électeurs ou consulter les tableaux de bord : tout membre du bureau. */
    public const DASHBOARD = ['president', 'vice_president', 'secretary', 'treasurer', 'sports', 'communication', 'external_relations', 'other'];
}

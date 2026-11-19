<?php

return [
    // Rôles pour lesquels la double authentification est obligatoire (séparés par des virgules).
    // Recommandé en production : "super_admin,consulate_admin". Vide = facultative pour tous.
    'two_factor_required_roles' => env('TWO_FACTOR_REQUIRED_ROLES', ''),

    // Obligatoire pour tout compte qui gère les élections, le bureau ou les rôles (super admin, consul, gestion des utilisateurs).
    'two_factor_for_managers' => env('TWO_FACTOR_FOR_MANAGERS', true),

];

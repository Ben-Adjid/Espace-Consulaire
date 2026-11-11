<?php

return [
    'required' => 'Le champ :attribute est obligatoire.',
    'string' => 'Le champ :attribute doit être un texte.',
    'email' => 'Le champ :attribute doit être une adresse email valide.',
    'integer' => 'Le champ :attribute doit être un nombre entier.',
    'date' => 'Le champ :attribute n’est pas une date valide.',
    'date_format' => 'Le champ :attribute ne correspond pas au format attendu.',
    'regex' => 'Le format du champ :attribute est invalide.',
    'exists' => 'La valeur choisie pour :attribute est invalide.',
    'min' => ['string' => 'Le champ :attribute doit contenir au moins :min caractères.', 'numeric' => 'Le champ :attribute doit être au moins :min.'],
    'max' => ['string' => 'Le champ :attribute ne doit pas dépasser :max caractères.', 'numeric' => 'Le champ :attribute ne doit pas dépasser :max.'],
    'unique' => 'La valeur de :attribute est déjà utilisée.',
    'attributes' => [],
];

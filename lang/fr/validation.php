<?php

return [
    'required' => 'Le champ :attribute est obligatoire.',
    'email' => 'Le champ :attribute doit être une adresse email valide.',
    'unique' => 'Cette valeur de :attribute est déjà utilisée.',
    'min' => [
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'attributes' => [
        'name' => 'nom utilisateur',
        'email' => 'login',
        'password' => 'mot de passe',
        'role' => 'profil',
    ],
    'image' => 'image',
    'category_image' => 'image de la catégorie',
    'brand_image' => 'image de la marque',
    'mimes' => 'Le fichier doit être au format :values.',
    'max' => [
        'file' => 'Le fichier ne doit pas dépasser :max kilo-octets.',
    ],
    'uploaded' => 'Le fichier n\'a pas pu être téléchargé. Utilisez JPG, PNG ou WebP (max. 2 Mo).',
];

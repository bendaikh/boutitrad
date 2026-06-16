<?php

/**
 * Villes Cathedis par défaut (tarifs Pack Silver / Gold — cathedis.ma).
 * Complétez via `php artisan cathedis:sync-cities` quand l'API est configurée.
 */
return [
    'default_pickup_city' => 'Casablanca',
    'default_pack' => env('CATHEDIS_PACK', 'silver'),

    'cities' => [
        ['name' => 'Casablanca', 'zone' => 'ramassage', 'sort_order' => 1],
        ['name' => 'Marrakech', 'zone' => 'grande', 'sort_order' => 2],
        ['name' => 'Tanger', 'zone' => 'grande', 'sort_order' => 3],
        ['name' => 'Fès', 'zone' => 'grande', 'sort_order' => 4],
        ['name' => 'Salé', 'zone' => 'grande', 'sort_order' => 5],
        ['name' => 'Meknès', 'zone' => 'grande', 'sort_order' => 6],
        ['name' => 'Oujda', 'zone' => 'grande', 'sort_order' => 7],
        ['name' => 'Kénitra', 'zone' => 'grande', 'sort_order' => 8],
        ['name' => 'Tétouan', 'zone' => 'grande', 'sort_order' => 9],
        ['name' => 'Témara', 'zone' => 'grande', 'sort_order' => 10],
        ['name' => 'Safi', 'zone' => 'grande', 'sort_order' => 11],
        ['name' => 'Mohammedia', 'zone' => 'grande', 'sort_order' => 12],
        ['name' => 'Khouribga', 'zone' => 'grande', 'sort_order' => 13],
        ['name' => 'El Jadida', 'zone' => 'grande', 'sort_order' => 14],
        ['name' => 'Beni Mellal', 'zone' => 'grande', 'sort_order' => 15],
        ['name' => 'Nador', 'zone' => 'grande', 'sort_order' => 16],
        ['name' => 'Taza', 'zone' => 'grande', 'sort_order' => 17],
        ['name' => 'Khémisset', 'zone' => 'grande', 'sort_order' => 18],
        ['name' => 'Berkane', 'zone' => 'grande', 'sort_order' => 19],
        ['name' => 'Rabat', 'zone' => 'grande', 'sort_order' => 20],
        ['name' => 'Agadir', 'zone' => 'grande', 'sort_order' => 21],
        ['name' => 'Settat', 'zone' => 'petite', 'sort_order' => 30],
        ['name' => 'Berrechid', 'zone' => 'petite', 'sort_order' => 31],
        ['name' => 'Ksar El Kébir', 'zone' => 'petite', 'sort_order' => 32],
        ['name' => 'Larache', 'zone' => 'petite', 'sort_order' => 33],
        ['name' => 'Guelmim', 'zone' => 'sud', 'sort_order' => 40],
        ['name' => 'Laâyoune', 'zone' => 'sud', 'sort_order' => 41],
        ['name' => 'Dakhla', 'zone' => 'sud', 'sort_order' => 42],
        ['name' => 'Tan-Tan', 'zone' => 'sud', 'sort_order' => 43],
        ['name' => 'Zagora', 'zone' => 'sud', 'sort_order' => 44],
        ['name' => 'Errachidia', 'zone' => 'sud', 'sort_order' => 45],
        ['name' => 'Ouarzazate', 'zone' => 'sud', 'sort_order' => 46],
    ],
];

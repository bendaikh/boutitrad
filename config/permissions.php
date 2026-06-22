<?php

return [
    'groups' => [
        [
            'key' => 'dashboard',
            'label' => 'Tableau de bord',
            'permissions' => [
                ['key' => 'dashboard.access', 'label' => 'Accès', 'short' => 'TB'],
            ],
        ],
        [
            'key' => 'clients',
            'label' => 'Clients',
            'sections' => [
                [
                    'label' => 'Fiche client',
                    'permissions' => [
                        ['key' => 'clients.create', 'label' => 'Saisir', 'short' => 'S'],
                        ['key' => 'clients.view', 'label' => 'Voir', 'short' => 'V'],
                        ['key' => 'clients.update', 'label' => 'Modifier', 'short' => 'M'],
                        ['key' => 'clients.delete', 'label' => 'Supprimer', 'short' => 'X'],
                    ],
                ],
                [
                    'label' => 'Balance client',
                    'permissions' => [
                        ['key' => 'clients.balance.view', 'label' => 'Voir', 'short' => 'V'],
                        ['key' => 'clients.balance.print', 'label' => 'Imprimer', 'short' => 'I'],
                    ],
                ],
            ],
        ],
        [
            'key' => 'stock',
            'label' => 'Stock',
            'sections' => [
                [
                    'label' => 'Produit',
                    'permissions' => [
                        ['key' => 'products.view', 'label' => 'Voir', 'short' => 'V'],
                        ['key' => 'products.create', 'label' => 'Saisir', 'short' => 'S'],
                        ['key' => 'products.update', 'label' => 'Modifier', 'short' => 'M'],
                        ['key' => 'products.delete', 'label' => 'Supprimer', 'short' => 'X'],
                    ],
                ],
                [
                    'label' => 'Catégorie',
                    'permissions' => [
                        ['key' => 'categories.view', 'label' => 'Voir', 'short' => 'V'],
                        ['key' => 'categories.create', 'label' => 'Saisir', 'short' => 'S'],
                        ['key' => 'categories.update', 'label' => 'Modifier', 'short' => 'M'],
                        ['key' => 'categories.delete', 'label' => 'Supprimer', 'short' => 'X'],
                    ],
                ],
                [
                    'label' => 'Stock',
                    'permissions' => [
                        ['key' => 'stock.view', 'label' => 'Voir', 'short' => 'V'],
                        ['key' => 'stock.print', 'label' => 'Imprimer', 'short' => 'I'],
                    ],
                ],
            ],
        ],
        [
            'key' => 'ventes',
            'label' => 'Ventes',
            'sections' => [
                [
                    'label' => 'Commandes',
                    'permissions' => [
                        ['key' => 'orders.view', 'label' => 'Voir', 'short' => 'V'],
                        ['key' => 'orders.validate', 'label' => 'Valider', 'short' => 'Val'],
                        ['key' => 'orders.create', 'label' => 'Saisir', 'short' => 'S'],
                        ['key' => 'orders.update', 'label' => 'Modifier', 'short' => 'M'],
                        ['key' => 'orders.delete', 'label' => 'Supprimer', 'short' => 'X'],
                    ],
                ],
                [
                    'label' => 'Balance',
                    'permissions' => [
                        ['key' => 'sales.balance.view', 'label' => 'Voir', 'short' => 'V'],
                        ['key' => 'sales.balance.print', 'label' => 'Imprimer', 'short' => 'I'],
                    ],
                ],
                [
                    'label' => 'Paie Commerciaux',
                    'permissions' => [
                        ['key' => 'payments.view', 'label' => 'Voir', 'short' => 'V'],
                        ['key' => 'payments.create', 'label' => 'Saisir', 'short' => 'S'],
                        ['key' => 'payments.update', 'label' => 'Modifier', 'short' => 'M'],
                        ['key' => 'payments.delete', 'label' => 'Supprimer', 'short' => 'X'],
                    ],
                ],
            ],
        ],
        [
            'key' => 'configuration',
            'label' => 'Configuration',
            'sections' => [
                [
                    'label' => 'Commerciaux',
                    'permissions' => [
                        ['key' => 'commercials.view', 'label' => 'Voir', 'short' => 'V'],
                        ['key' => 'commercials.create', 'label' => 'Saisir', 'short' => 'S'],
                        ['key' => 'commercials.update', 'label' => 'Modifier', 'short' => 'M'],
                        ['key' => 'commercials.delete', 'label' => 'Supprimer', 'short' => 'X'],
                    ],
                ],
            ],
        ],
    ],

    'defaults' => [
        'superadmin' => ['*'],
        'commercial' => [
            'dashboard.access',
            'clients.create', 'clients.view', 'clients.update',
            'clients.balance.view', 'clients.balance.print',
            'orders.view', 'orders.create', 'orders.update',
            'commercials.view',
            'sales.balance.view', 'sales.balance.print',
            'payments.view',
            'stock.view', 'stock.print',
        ],
        'gestionnaire_stock' => [
            'dashboard.access',
            'products.view', 'products.create', 'products.update',
            'categories.view', 'categories.create', 'categories.update',
            'stock.view', 'stock.print',
        ],
        'livreur' => [
            'dashboard.access',
        ],
    ],
];

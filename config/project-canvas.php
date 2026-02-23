<?php

return [
    'name' => 'Project Canvas',
    'description' => 'Project Canvas Module',
    'version' => '1.0.0',

    'routing' => [
        'prefix' => 'project-canvas',
        'middleware' => ['web', 'auth'],
    ],

    'guard' => 'web',

    'navigation' => [
        'main' => [
            'project-canvas' => [
                'title' => 'Project Canvas',
                'icon' => 'heroicon-o-clipboard-document-list',
                'route' => 'project-canvas.dashboard',
            ],
        ],
    ],

    'sidebar' => [
        'project-canvas' => [
            'title' => 'Project Canvas',
            'icon' => 'heroicon-o-clipboard-document-list',
            'items' => [
                'dashboard' => [
                    'title' => 'Dashboard',
                    'route' => 'project-canvas.dashboard',
                    'icon' => 'heroicon-o-home',
                ],
                'canvases' => [
                    'title' => 'Canvases',
                    'route' => 'project-canvas.canvases.index',
                    'icon' => 'heroicon-o-clipboard-document-list',
                ],
            ],
        ],
    ],
];

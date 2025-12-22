<?php

return [
    /*
    |--------------------------------------------------------------------------
    | File Upload Validations
    |--------------------------------------------------------------------------
    |
    | Configuración de validaciones por modelo y campo.
    | Define las reglas de validación para cada tipo de archivo.
    |
    */

    'validations' => [
        'campaign' => [
            'banner' => [
                'rules' => ['image', 'max:2048', 'dimensions:min_width=800'],
                'mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
            ],
            'cover_image' => [
                'rules' => ['image', 'max:2048'],
                'mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
            ],
            'logo_url' => [
                'rules' => ['image', 'max:512', 'dimensions:max_width=200,max_height=200'],
                'mime_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'],
            ],
        ],
        'business' => [
            'logo' => [
                'rules' => ['image', 'max:1024'],
                'mime_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'],
            ],
            'cover' => [
                'rules' => ['image', 'max:2048'],
                'mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
            ],
        ],
        'user' => [
            'avatar' => [
                'rules' => ['image', 'max:512', 'dimensions:max_width=200,max_height=200'],
                'mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
            ],
        ],
        'reward' => [
            'image_url' => [
                'rules' => ['image', 'max:1024'],
                'mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración del almacenamiento de archivos.
    |
    */

    'disk' => 'public',
    'path_prefix' => 'uploads',

    /*
    |--------------------------------------------------------------------------
    | File Naming Strategy
    |--------------------------------------------------------------------------
    |
    | Estrategia para nombrar archivos subidos.
    | Opciones: 'timestamp', 'uuid', 'original'
    |
    */

    'naming_strategy' => 'timestamp',

];


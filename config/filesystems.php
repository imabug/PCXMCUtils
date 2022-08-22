<?php
    return [
        'default' => 'local',
        'disks' => [
            'local' => [
                'driver' => 'local',
                'root' => storage_path('app'),
            ],
            'simulations' => [
                'driver' => 'local',
                'root' => storage_path('simulations'),
            ],
        ],
    ];

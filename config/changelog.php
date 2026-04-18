<?php

return [
    'queue' => [
        'connection' => env('CHANGELOG_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'redis')),
        'name' => env('CHANGELOG_QUEUE_NAME', 'changelog'),
    ],

    'default_repository' => env('CHANGELOG_DEFAULT_REPOSITORY', 'local/repository'),
];

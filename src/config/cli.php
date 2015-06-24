<?php

$config = [
    'controllerMap' => [
        'presql' => '\luya\commands\PresqlController',
        'exec' => '\luya\commands\ExecutableController',
        'crud' => '\luya\commands\CrudController',
        'command' => '\luya\commands\CommandController',
    ],
    'components' => [
        'mail' => [
            'class' => '\luya\components\Mail',
        ],
        'errorHandler' => [
            'class' => '\luya\base\cli\ErrorHandler',
            'memoryReserveSize' => 0,
        ],
    ],
    'bootstrap' => [
        'luya\base\cli\Bootstrap',
    ],
    'timezone' => 'Europe/Berlin',
];

return $config;

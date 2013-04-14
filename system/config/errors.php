<?php

return [
    'global' => [
        'ignore' => E_NOTICE | E_WARNING,
        'debug' => true
    ],

    'production' => [
        'ignore' => E_ALL,
        'debug' => false
    ]
];
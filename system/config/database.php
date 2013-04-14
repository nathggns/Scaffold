<?php defined('SCAFFOLD') or die;

/*
 * Database connection details.
 */
return [

    // Default settings
    'global' => [
        'host' => '127.0.0.1',
        'username' => 'root',
        'password' => '',
        'database' => 'scaffold',
        'type' => 'mysql'
    ],

    'testing' => [
        'dsn' => 'sqlite:test.db',
        'type' => 'sqlite'
    ]

];
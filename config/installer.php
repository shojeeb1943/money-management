<?php

declare(strict_types=1);

return [

    'php_version' => '8.3.0',

    'extensions' => [
        'ctype',
        'curl',
        'dom',
        'fileinfo',
        'filter',
        'hash',
        'mbstring',
        'openssl',
        'session',
        'tokenizer',
        'xml',
    ],

    'drivers' => [
        'sqlite' => 'pdo_sqlite',
        'mysql' => 'pdo_mysql',
        'mariadb' => 'pdo_mysql',
        'pgsql' => 'pdo_pgsql',
        'sqlsrv' => 'pdo_sqlsrv',
    ],

];

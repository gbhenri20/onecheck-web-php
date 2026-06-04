<?php

declare(strict_types=1);

/**
 * Conexão PDO — PostgreSQL (padrão) ou MySQL (XAMPP local).
 *
 * Render: use DATABASE_URL (injetada pelo Blueprint) ou variáveis ONECHECK_DB_*.
 * Local PostgreSQL: ONECHECK_DB_DRIVER=pgsql (padrão), host/porta/usuário abaixo.
 * Local XAMPP MySQL: ONECHECK_DB_DRIVER=mysql e porta 3306 ou 3307.
 */
$driver = getenv('ONECHECK_DB_DRIVER') ?: 'pgsql';
$defaultPort = $driver === 'mysql' ? 3306 : 5432;
$portEnv = getenv('ONECHECK_DB_PORT');

return [
    'driver'   => $driver,
    'url'      => getenv('DATABASE_URL') ?: getenv('ONECHECK_DATABASE_URL') ?: null,
    'host'     => getenv('ONECHECK_DB_HOST') ?: '127.0.0.1',
    'port'     => $portEnv !== false && $portEnv !== '' ? (int) $portEnv : $defaultPort,
    'database' => getenv('ONECHECK_DB_NAME') ?: ($driver === 'mysql' ? 'onecheck' : 'onecheck'),
    'username' => getenv('ONECHECK_DB_USER') ?: ($driver === 'mysql' ? 'root' : 'postgres'),
    'password' => getenv('ONECHECK_DB_PASS') !== false ? (string) getenv('ONECHECK_DB_PASS') : '',
    'charset'  => 'utf8',
];

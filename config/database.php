<?php

declare(strict_types=1);

/**
 * Conexão PDO com MySQL.
 * Ajuste host, banco, usuário e senha conforme seu ambiente (XAMPP, Laragon, etc.).
 */
return [
    'host'     => getenv('ONECHECK_DB_HOST') ?: '127.0.0.1',
    // XAMPP neste PC usa porta 3307 (3306 costuma estar ocupada por outro MySQL)
    'port'     => (int) (getenv('ONECHECK_DB_PORT') ?: 3306),
    'database' => getenv('ONECHECK_DB_NAME') ?: 'onecheck',
    'username' => getenv('ONECHECK_DB_USER') ?: 'root',
    'password' => getenv('ONECHECK_DB_PASS') ?: '',
    'charset'  => 'utf8mb4',
];

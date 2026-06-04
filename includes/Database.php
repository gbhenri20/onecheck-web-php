<?php

declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $cfg = require ONECHECK_ROOT . '/config/database.php';

        if (!empty($cfg['url'])) {
            $parsed = self::parseUrl((string) $cfg['url']);
            $dsn = sprintf(
                '%s:host=%s;port=%d;dbname=%s',
                $parsed['driver'],
                $parsed['host'],
                $parsed['port'],
                $parsed['database']
            );
            $user = $parsed['user'];
            $pass = $parsed['password'];
        } else {
            $driver = ($cfg['driver'] ?? 'pgsql') === 'mysql' ? 'mysql' : 'pgsql';
            $dsn = sprintf(
                '%s:host=%s;port=%d;dbname=%s',
                $driver,
                $cfg['host'],
                $cfg['port'],
                $cfg['database']
            );
            if ($driver === 'mysql') {
                $dsn .= ';charset=' . ($cfg['charset'] ?? 'utf8mb4');
            }
            $user = $cfg['username'];
            $pass = $cfg['password'];
        }

        self::$pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        return self::$pdo;
    }

    /** @return array{driver: string, host: string, port: int, database: string, user: string, password: string} */
    private static function parseUrl(string $url): array
    {
        $parsed = parse_url($url);
        if ($parsed === false) {
            throw new InvalidArgumentException('DATABASE_URL inválida');
        }

        $scheme = $parsed['scheme'] ?? 'postgresql';
        $driver = in_array($scheme, ['postgres', 'postgresql'], true) ? 'pgsql' : 'mysql';

        return [
            'driver'   => $driver,
            'host'     => $parsed['host'] ?? '127.0.0.1',
            'port'     => (int) ($parsed['port'] ?? ($driver === 'pgsql' ? 5432 : 3306)),
            'database' => ltrim($parsed['path'] ?? '', '/'),
            'user'     => urldecode($parsed['user'] ?? ''),
            'password' => urldecode($parsed['pass'] ?? ''),
        ];
    }
}

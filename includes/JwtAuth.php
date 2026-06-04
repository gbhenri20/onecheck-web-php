<?php

declare(strict_types=1);

final class JwtAuth
{
    public static function issueAccessToken(array $user): string
    {
        $cfg = require ONECHECK_ROOT . '/config/auth.php';
        return self::encode([
            'sub'    => (int) $user['id'],
            'email'  => $user['email'],
            'perfil' => $user['perfil'],
            'type'   => 'access',
        ], (int) $cfg['access_ttl']);
    }

    public static function issueMfaPendingToken(array $user): string
    {
        $cfg = require ONECHECK_ROOT . '/config/auth.php';
        return self::encode([
            'sub'   => (int) $user['id'],
            'email' => $user['email'],
            'type'  => 'mfa_pending',
        ], (int) $cfg['mfa_pending_ttl']);
    }

    public static function issueRefreshToken(int $userId): string
    {
        $raw = bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);
        $cfg = require ONECHECK_ROOT . '/config/auth.php';
        $expira = (new DateTimeImmutable('+' . (int) $cfg['refresh_ttl'] . ' seconds'))
            ->format('Y-m-d H:i:s');

        Database::pdo()->prepare(
            'INSERT INTO auth_refresh_tokens (id, usuario_id, token_hash, expira_em, ip, user_agent)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            self::uuid(),
            $userId,
            $hash,
            $expira,
            $_SERVER['REMOTE_ADDR'] ?? null,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500) ?: null,
        ]);

        return $raw;
    }

    public static function refresh(string $refreshToken): ?array
    {
        $hash = hash('sha256', $refreshToken);
        $stmt = Database::pdo()->prepare(
            'SELECT t.usuario_id, u.id, u.nome, u.email, u.perfil, u.ativo
             FROM auth_refresh_tokens t
             INNER JOIN usuarios u ON u.id = t.usuario_id
             WHERE t.token_hash = ? AND t.revogado = 0 AND t.expira_em > NOW()
             LIMIT 1'
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch();

        if (!$row || !(int) $row['ativo']) {
            return null;
        }

        $user = [
            'id'     => (int) $row['id'],
            'nome'   => $row['nome'],
            'email'  => $row['email'],
            'perfil' => $row['perfil'],
        ];

        return [
            'access_token'  => self::issueAccessToken($user),
            'refresh_token' => self::issueRefreshToken($user['id']),
            'usuario'       => $user,
        ];
    }

    public static function validateAccessToken(string $token): ?array
    {
        $payload = self::decode($token);
        if (!$payload || ($payload['type'] ?? '') !== 'access') {
            return null;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT id, nome, email, perfil, ativo FROM usuarios WHERE id = ? LIMIT 1'
        );
        $stmt->execute([(int) $payload['sub']]);
        $user = $stmt->fetch();

        if (!$user || !(int) $user['ativo']) {
            return null;
        }

        return [
            'id'     => (int) $user['id'],
            'nome'   => $user['nome'],
            'email'  => $user['email'],
            'perfil' => $user['perfil'],
        ];
    }

    public static function validateMfaPendingToken(string $token): ?array
    {
        $payload = self::decode($token);
        if (!$payload || ($payload['type'] ?? '') !== 'mfa_pending') {
            return null;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT id, nome, email, perfil, mfa_secret, mfa_enabled, ativo FROM usuarios WHERE id = ? LIMIT 1'
        );
        $stmt->execute([(int) $payload['sub']]);
        $user = $stmt->fetch();

        return $user && (int) $user['ativo'] ? $user : null;
    }

    private static function encode(array $payload, int $ttlSeconds): string
    {
        $cfg = require ONECHECK_ROOT . '/config/auth.php';
        $header = self::b64(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload['iat'] = time();
        $payload['exp'] = time() + $ttlSeconds;
        $body = self::b64(json_encode($payload));
        $sig = self::b64(hash_hmac('sha256', "{$header}.{$body}", $cfg['jwt_secret'], true));
        return "{$header}.{$body}.{$sig}";
    }

    private static function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        $cfg = require ONECHECK_ROOT . '/config/auth.php';
        $expected = self::b64(hash_hmac('sha256', "{$parts[0]}.{$parts[1]}", $cfg['jwt_secret'], true));
        if (!hash_equals($expected, $parts[2])) {
            return null;
        }

        $payload = json_decode(self::b64Decode($parts[1]), true);
        if (!is_array($payload) || ($payload['exp'] ?? 0) < time()) {
            return null;
        }

        return $payload;
    }

    private static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function b64Decode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'), true) ?: '';
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

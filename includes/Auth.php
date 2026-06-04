<?php

declare(strict_types=1);

final class Auth
{
    /**
     * Login web passo 1 — valida senha e define próximo passo (MFA ou concluir).
     * @return array{status: string, user?: array}
     */
    public static function loginWithPassword(string $email, string $password): array
    {
        $user = self::fetchUserByEmail($email);

        if (!$user || !(int) $user['ativo'] || !password_verify($password, $user['senha_hash'])) {
            return ['status' => 'invalid'];
        }

        $mfaObrigatorio = (int) ($user['mfa_obrigatorio'] ?? 0)
            || Mfa::isMandatoryForProfile($user['perfil']);
        $mfaEnabled = (int) ($user['mfa_enabled'] ?? 0);

        if ($mfaObrigatorio && !$mfaEnabled) {
            self::setMfaPending((int) $user['id'], 'setup');
            return ['status' => 'mfa_setup', 'user' => $user];
        }

        if ($mfaEnabled) {
            self::setMfaPending((int) $user['id'], 'verify');
            return ['status' => 'mfa_verify', 'user' => $user];
        }

        self::completeWebLogin($user);
        return ['status' => 'ok', 'user' => $user];
    }

    public static function completeWebLogin(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'     => (int) $user['id'],
            'nome'   => $user['nome'],
            'email'  => $user['email'],
            'perfil' => $user['perfil'],
        ];
        unset($_SESSION['mfa_pending']);
        AuditLog::record('login', 'usuarios', (string) $user['id']);
    }

    public static function setMfaPending(int $userId, string $mode): void
    {
        $cfg = require ONECHECK_ROOT . '/config/auth.php';
        $_SESSION['mfa_pending'] = [
            'user_id' => $userId,
            'mode'    => $mode,
            'until'   => time() + (int) $cfg['mfa_pending_ttl'],
        ];
    }

    public static function mfaPending(): ?array
    {
        $p = $_SESSION['mfa_pending'] ?? null;
        if (!$p || ($p['until'] ?? 0) < time()) {
            unset($_SESSION['mfa_pending']);
            return null;
        }
        return $p;
    }

    public static function fetchUserById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, nome, email, senha_hash, perfil, ativo, mfa_secret, mfa_enabled, mfa_obrigatorio
             FROM usuarios WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function fetchUserByEmail(string $email): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, nome, email, senha_hash, perfil, ativo, mfa_secret, mfa_enabled, mfa_obrigatorio
             FROM usuarios WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @deprecated use loginWithPassword */
    public static function attempt(string $email, string $password): bool
    {
        $r = self::loginWithPassword($email, $password);
        return $r['status'] === 'ok';
    }

    public static function enableMfa(int $userId, string $secret, string $code): bool
    {
        if (!Mfa::verify($secret, $code)) {
            return false;
        }

        Database::pdo()->prepare(
            'UPDATE usuarios SET mfa_secret = ?, mfa_enabled = 1 WHERE id = ?'
        )->execute([$secret, $userId]);

        AuditLog::record('update', 'usuarios', (string) $userId, null, ['mfa_enabled' => true]);
        return true;
    }

    public static function verifyMfaAndLogin(int $userId, string $code): bool
    {
        $user = self::fetchUserById($userId);
        if (!$user || !(int) $user['mfa_enabled'] || empty($user['mfa_secret'])) {
            return false;
        }
        if (!Mfa::verify($user['mfa_secret'], $code)) {
            return false;
        }
        self::completeWebLogin($user);
        return true;
    }

    public static function logout(): void
    {
        if ($u = self::user()) {
            AuditLog::record('logout', 'usuarios', (string) $u['id']);
        }
        unset($_SESSION['user'], $_SESSION['mfa_pending']);
        session_regenerate_id(true);
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']['id']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect(base_url('public/login.php'));
        }
    }

    public static function requireRole(string ...$perfis): void
    {
        self::requireLogin();
        $user = self::user();
        if (!in_array($user['perfil'] ?? '', $perfis, true)) {
            flash_set('error', 'Acesso não permitido para seu perfil.');
            redirect(self::homeUrl());
        }
    }

    public static function homeUrl(): string
    {
        $user = self::user();
        if ($user && $user['perfil'] === 'locatario') {
            return base_url('locatario/index.php');
        }
        return base_url('dashboard/index.php');
    }

    public static function can(string $permission): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        $map = require ONECHECK_ROOT . '/config/permissions.php';
        $perfil = $user['perfil'];
        $allowed = $map[$perfil] ?? [];

        if (in_array('*', $allowed, true)) {
            return true;
        }

        foreach ($allowed as $item) {
            if ($item === $permission) {
                return true;
            }
            if (str_ends_with($item, '.view') && str_starts_with($permission, rtrim($item, '.view'))) {
                return true;
            }
        }

        return false;
    }

    /** API: JWT access ou token legado */
    public static function userFromBearer(string $token): ?array
    {
        $jwtUser = JwtAuth::validateAccessToken($token);
        if ($jwtUser) {
            return $jwtUser;
        }
        return self::userFromApiToken($token);
    }

    public static function userFromApiToken(string $token): ?array
    {
        $hash = hash('sha256', $token);
        $stmt = Database::pdo()->prepare(
            'SELECT u.id, u.nome, u.email, u.perfil, u.ativo
             FROM api_tokens t
             INNER JOIN usuarios u ON u.id = t.usuario_id
             WHERE t.token_hash = ? AND t.revogado = 0
               AND (t.expira_em IS NULL OR t.expira_em > NOW())
             LIMIT 1'
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch();

        if (!$row || !(int) $row['ativo']) {
            return null;
        }

        Database::pdo()->prepare('UPDATE api_tokens SET ultimo_uso = NOW() WHERE token_hash = ?')
            ->execute([$hash]);

        return [
            'id'     => (int) $row['id'],
            'nome'   => $row['nome'],
            'email'  => $row['email'],
            'perfil' => $row['perfil'],
        ];
    }

    public static function createApiToken(int $userId, ?string $dispositivo = null, int $days = 30): string
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expira = (new DateTimeImmutable("+{$days} days"))->format('Y-m-d H:i:s');

        Database::pdo()->prepare(
            'INSERT INTO api_tokens (usuario_id, token_hash, dispositivo, expira_em) VALUES (?, ?, ?, ?)'
        )->execute([$userId, $hash, $dispositivo, $expira]);

        return $token;
    }

    /** Resposta padrão de tokens JWT após login/MFA na API */
    public static function apiTokenResponse(array $user, ?string $dispositivo = null): array
    {
        return [
            'access_token'  => JwtAuth::issueAccessToken($user),
            'refresh_token' => JwtAuth::issueRefreshToken((int) $user['id']),
            'token_type'    => 'Bearer',
            'expires_in'    => (require ONECHECK_ROOT . '/config/auth.php')['access_ttl'],
            'usuario'       => [
                'id'     => (int) $user['id'],
                'nome'   => $user['nome'],
                'email'  => $user['email'],
                'perfil' => $user['perfil'],
            ],
            'legacy_token'  => self::createApiToken((int) $user['id'], $dispositivo),
        ];
    }
}

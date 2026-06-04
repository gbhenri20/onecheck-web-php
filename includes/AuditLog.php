<?php

declare(strict_types=1);

final class AuditLog
{
    public static function record(
        string $acao,
        string $entidade,
        ?string $entidadeId = null,
        ?array $anterior = null,
        ?array $novo = null
    ): void {
        try {
            $user = Auth::user();
            Database::pdo()->prepare(
                'INSERT INTO log_operacao (id, usuario_id, acao, entidade, entidade_id, payload_anterior, payload_novo, ip, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                self::uuid(),
                $user['id'] ?? null,
                $acao,
                $entidade,
                $entidadeId,
                $anterior ? json_encode($anterior, JSON_UNESCAPED_UNICODE) : null,
                $novo ? json_encode($novo, JSON_UNESCAPED_UNICODE) : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500) ?: null,
            ]);
        } catch (Throwable) {
            // Não interrompe fluxo se log falhar
        }
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

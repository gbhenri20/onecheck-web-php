<?php
/**
 * JWT compatível com a API FastAPI (python-jose) para fluxo MFA no login web.
 * Requer ONECHECK_JWT_SECRET igual ao JWT_SECRET da API no Render.
 */

function api_jwt_secret(): string
{
    $cfg = require ONECHECK_ROOT . '/config/auth.php';
    $secret = getenv('ONECHECK_JWT_SECRET') ?: getenv('JWT_SECRET') ?: ($cfg['jwt_secret'] ?? '');
    return (string) $secret;
}

function api_jwt_b64(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/** Token temporário MFA — mesmo formato de create_temp_token() na API. */
function api_issue_mfa_temp_token(string $userId): string
{
    $secret = api_jwt_secret();
    if ($secret === '') {
        throw new RuntimeException('ONECHECK_JWT_SECRET não configurado no servidor web.');
    }

    $header = api_jwt_b64(json_encode(['typ' => 'JWT', 'alg' => 'HS256'], JSON_UNESCAPED_SLASHES));
    $exp = time() + 600;
    $body = api_jwt_b64(json_encode([
        'sub'  => $userId,
        'type' => 'mfa',
        'exp'  => $exp,
    ], JSON_UNESCAPED_SLASHES));
    $sig = api_jwt_b64(hash_hmac('sha256', "{$header}.{$body}", $secret, true));

    return "{$header}.{$body}.{$sig}";
}

function api_jwt_configured(): bool
{
    return api_jwt_secret() !== '';
}

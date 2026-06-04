<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

api_require_method('POST');

$input = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($input)) {
    $input = $_POST;
}

$pendingToken = trim($input['mfa_pending_token'] ?? $input['token'] ?? '');
$code = trim($input['code'] ?? '');

if ($pendingToken === '' || $code === '') {
    api_error('Informe mfa_pending_token e code', 422);
}

$user = JwtAuth::validateMfaPendingToken($pendingToken);
if (!$user) {
    api_error('Sessão MFA expirada. Faça login novamente.', 401);
}

if (!Mfa::verify($user['mfa_secret'], $code)) {
    api_error('Código MFA inválido', 401);
}

$dispositivo = trim($input['dispositivo'] ?? 'app');
AuditLog::record('login', 'usuarios', (string) $user['id'], null, ['via' => 'api_mfa']);

api_ok(Auth::apiTokenResponse($user, $dispositivo));

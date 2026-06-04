<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

api_require_method('POST');

$input = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($input)) {
    $input = $_POST;
}

$email = trim($input['email'] ?? '');
$senha = $input['senha'] ?? $input['password'] ?? '';
$dispositivo = trim($input['dispositivo'] ?? $input['device'] ?? 'app');

if ($email === '' || $senha === '') {
    api_error('Informe email e senha', 422);
}

$user = Auth::fetchUserByEmail($email);

if (!$user || !(int) $user['ativo'] || !password_verify($senha, $user['senha_hash'])) {
    api_error('Credenciais inválidas', 401);
}

$mfaObrigatorio = (int) ($user['mfa_obrigatorio'] ?? 0) || Mfa::isMandatoryForProfile($user['perfil']);
$mfaEnabled = (int) ($user['mfa_enabled'] ?? 0);

if ($mfaObrigatorio && !$mfaEnabled) {
    api_error('Configure o MFA no painel web antes de usar o app', 403, ['mfa_setup_required' => true]);
}

if ($mfaEnabled) {
    api_ok([
        'mfa_required'    => true,
        'mfa_pending_token' => JwtAuth::issueMfaPendingToken($user),
        'usuario'         => [
            'id'    => (int) $user['id'],
            'email' => $user['email'],
            'nome'  => $user['nome'],
        ],
    ]);
}

api_ok(Auth::apiTokenResponse($user, $dispositivo));

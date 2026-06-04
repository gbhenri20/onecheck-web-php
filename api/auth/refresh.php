<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

api_require_method('POST');

$input = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($input)) {
    $input = $_POST;
}

$refresh = trim($input['refresh_token'] ?? '');

if ($refresh === '') {
    api_error('Informe refresh_token', 422);
}

$result = JwtAuth::refresh($refresh);
if (!$result) {
    api_error('Refresh token inválido ou expirado', 401);
}

api_ok($result);

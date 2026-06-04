<?php

declare(strict_types=1);

function api_json(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_error(string $message, int $status = 400, array $extra = []): never
{
    api_json(array_merge(['ok' => false, 'error' => $message], $extra), $status);
}

function api_ok(mixed $data = [], int $status = 200): never
{
    api_json(array_merge(['ok' => true], is_array($data) ? $data : ['data' => $data]), $status);
}

function api_require_method(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        api_ok();
    }
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== strtoupper($method)) {
        api_error('Método não permitido', 405);
    }
}

function api_bearer_token(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
        return $m[1];
    }
    return $_GET['token'] ?? $_POST['token'] ?? null;
}

function api_auth_user(): array
{
    $token = api_bearer_token();
    if (!$token) {
        api_error('Token ausente', 401);
    }

    $user = Auth::userFromBearer($token);
    if (!$user) {
        api_error('Token inválido ou expirado', 401);
    }

    return $user;
}

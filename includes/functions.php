<?php

declare(strict_types=1);

function session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $cfg = require ONECHECK_ROOT . '/config/session.php';
    session_name($cfg['name']);
    session_set_cookie_params([
        'lifetime' => $cfg['lifetime'],
        'path'     => '/',
        'secure'   => $cfg['secure'],
        'httponly' => $cfg['httponly'],
        'samesite' => $cfg['samesite'],
    ]);
    session_start();
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function app_web_root(): string
{
    static $root = null;
    if ($root !== null) {
        return $root;
    }

    // Variável de ambiente (Render/Docker) tem prioridade
    $envBase = getenv('ONECHECK_BASE_PATH');
    if ($envBase !== false) {
        $root = rtrim(str_replace('\\', '/', (string) $envBase), '/');
        return $root;
    }

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');

    $modulos = [
        'dashboard', 'imoveis', 'vistorias', 'contratos', 'problemas',
        'usuarios', 'locatario', 'public', 'api',
    ];
    foreach ($modulos as $modulo) {
        $marcador = '/' . $modulo . '/';
        $pos = strpos($script, $marcador);
        if ($pos !== false) {
            $root = substr($script, 0, $pos);
            return $root === '/' ? '' : $root;
        }
        if (str_ends_with($script, '/' . $modulo)) {
            $root = dirname($script);
            return $root === '/' ? '' : $root;
        }
    }

    $dir = dirname($script);
    if (str_ends_with($dir, '/public')) {
        $root = dirname($dir);
        return $root === '/' ? '' : $root;
    }

    $cfg = ONECHECK_ROOT . '/config/app.php';
    if (is_file($cfg)) {
        $app = require $cfg;
        $configured = rtrim(str_replace('\\', '/', (string) ($app['base_path'] ?? '')), '/');
        if ($configured !== '') {
            $root = $configured;
            return $root;
        }
    }

    $root = '';
    return $root;
}

function base_url(string $path = ''): string
{
    $base = app_web_root();
    $path = ltrim(str_replace('\\', '/', $path), '/');

    if ($path === '') {
        return ($base === '' ? '' : $base) . '/';
    }

    return ($base === '' ? '' : $base) . '/' . $path;
}

function asset_url(string $path): string
{
    return base_url('assets/' . ltrim($path, '/'));
}

function uploads_path(string $sub = ''): string
{
    $root = ONECHECK_ROOT . '/assets/uploads';
    if (!is_dir($root)) {
        mkdir($root, 0755, true);
    }

    if ($sub !== '') {
        $full = $root . '/' . trim($sub, '/');
        if (!is_dir($full)) {
            mkdir($full, 0755, true);
        }
        return $full;
    }

    return $root;
}

function can(string $permission): bool
{
    return Auth::can($permission);
}

/** Monta payload de endereço para POST /imoveis/{id}/endereco */
function endereco_payload_from_post(): ?array
{
    $rua    = trim($_POST['rua'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = strtoupper(trim($_POST['estado'] ?? ''));
    if ($rua === '' || $cidade === '' || strlen($estado) !== 2) {
        return null;
    }

    $latRaw = trim($_POST['latitude'] ?? '');
    $lngRaw = trim($_POST['longitude'] ?? '');
    $latitude = $latRaw !== '' ? (float) str_replace(',', '.', $latRaw) : null;
    $longitude = $lngRaw !== '' ? (float) str_replace(',', '.', $lngRaw) : null;

    if ($latitude !== null && ($latitude < -90 || $latitude > 90)) {
        $latitude = null;
    }
    if ($longitude !== null && ($longitude < -180 || $longitude > 180)) {
        $longitude = null;
    }

    return [
        'rua'         => $rua,
        'numero'      => trim($_POST['numero'] ?? ''),
        'complemento' => trim($_POST['complemento'] ?? ''),
        'bairro'      => trim($_POST['bairro'] ?? ''),
        'cidade'      => $cidade,
        'estado'      => $estado,
        'cep'         => preg_replace('/\D/', '', $_POST['cep'] ?? ''),
    ] + ($latitude !== null ? ['latitude' => $latitude] : [])
      + ($longitude !== null ? ['longitude' => $longitude] : []);
}

/** Extrai latitude/longitude válidas de um endereço retornado pela API. */
function endereco_coords(?array $end): ?array
{
    if (!$end || !is_array($end)) {
        return null;
    }
    $lat = null;
    $lng = null;
    foreach (['latitude', 'lat'] as $k) {
        if (array_key_exists($k, $end) && $end[$k] !== null && $end[$k] !== '') {
            $lat = (float) str_replace(',', '.', (string) $end[$k]);
            break;
        }
    }
    foreach (['longitude', 'lng', 'lon'] as $k) {
        if (array_key_exists($k, $end) && $end[$k] !== null && $end[$k] !== '') {
            $lng = (float) str_replace(',', '.', (string) $end[$k]);
            break;
        }
    }
    if ($lat === null || $lng === null || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        return null;
    }
    if (abs($lat) < 0.000001 && abs($lng) < 0.000001) {
        return null;
    }
    return ['latitude' => $lat, 'longitude' => $lng];
}

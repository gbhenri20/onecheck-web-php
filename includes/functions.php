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

    $cfg = ONECHECK_ROOT . '/config/app.php';
    if (is_file($cfg)) {
        $app = require $cfg;
        if (!empty($app['base_path'])) {
            $root = rtrim(str_replace('\\', '/', (string) $app['base_path']), '/');
            return $root;
        }
    }

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    // Sobe até a raiz do projeto, independente da pasta atual (dashboard, imoveis, etc.)
    $modulos = ['dashboard', 'imoveis', 'vistorias', 'contratos', 'problemas', 'usuarios', 'public', 'api'];
    foreach ($modulos as $modulo) {
        $marcador = '/' . $modulo . '/';
        $pos = strpos($script, $marcador);
        if ($pos !== false) {
            $root = substr($script, 0, $pos);
            return $root === '' ? '' : $root;
        }
        if (str_ends_with($script, '/' . $modulo)) {
            $root = dirname($script);
            return $root === '/' ? '' : $root;
        }
    }

    $dir = dirname($script);
    $root = str_ends_with($dir, '/public') ? dirname($dir) : $dir;
    return $root === '/' ? '' : $root;
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

<?php
/**
 * Helpers de autenticação via API JWT
 */

function api_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function api_require_login(): void
{
    if (empty($_SESSION['api_token'])) {
        redirect(base_url('public/login.php'));
    }
}

function api_initials(): string
{
    $user = api_user();
    if (!$user) return 'U';
    $parts = explode(' ', $user['nome'] ?? '');
    return strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
}

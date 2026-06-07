<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';

if (!empty($_SESSION['api_token']) && !empty($_SESSION['api_refresh_token'])) {
    try {
        ApiClient::post('/auth/logout', ['refresh_token' => $_SESSION['api_refresh_token']]);
    } catch (Throwable) {
        // ignore
    }
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

session_start();
session_regenerate_id(true);
$_SESSION = [];

redirect(base_url('public/login.php?logout=1'));

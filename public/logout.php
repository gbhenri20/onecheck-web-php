<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';

if (!empty($_SESSION['api_token']) && !empty($_SESSION['api_refresh_token'])) {
    ApiClient::post('/auth/logout', ['refresh_token' => $_SESSION['api_refresh_token']]);
}

session_destroy();
redirect(base_url('public/login.php'));

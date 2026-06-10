<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/includes/auth_api.php';

if (!empty($_SESSION['api_token'])) {
    redirect(base_url('usuarios/mfa-configurar.php'));
}

redirect(base_url('public/login.php'));

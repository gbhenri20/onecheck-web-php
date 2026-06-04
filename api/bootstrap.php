<?php

declare(strict_types=1);

define('ONECHECK_ROOT', dirname(__DIR__));
require_once __DIR__ . '/geocode/Geocoder.php';
require_once ONECHECK_ROOT . '/includes/functions.php';
require_once ONECHECK_ROOT . '/includes/Database.php';
require_once ONECHECK_ROOT . '/includes/Mfa.php';
require_once ONECHECK_ROOT . '/includes/JwtAuth.php';
require_once ONECHECK_ROOT . '/includes/AuditLog.php';
require_once ONECHECK_ROOT . '/includes/Auth.php';
require_once ONECHECK_ROOT . '/api/helpers/response.php';

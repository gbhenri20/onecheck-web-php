<?php

declare(strict_types=1);

$base = getenv('ONECHECK_BASE_PATH');
if ($base === false) {
    $base = '/onecheck';
}
header('Location: ' . rtrim($base, '/') . '/public/login.php');
exit;

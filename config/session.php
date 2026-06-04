<?php

declare(strict_types=1);

return [
    'name'     => 'ONECHECK_SESSID',
    'lifetime' => 60 * 60 * 8, // 8 horas
    'secure'   => false,       // true em produção com HTTPS
    'httponly' => true,
    'samesite' => 'Lax',
];

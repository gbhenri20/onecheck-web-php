<?php

declare(strict_types=1);

/**
 * base_path: prefixo da URL (vazio = raiz do domínio, ex. Render).
 * Local XAMPP em subpasta: defina ONECHECK_BASE_PATH=/onecheck no Apache ou .htaccess.
 */
return [
    'base_path' => getenv('ONECHECK_BASE_PATH') !== false
        ? (string) getenv('ONECHECK_BASE_PATH')
        : '',
    'name'      => 'OneCheck',
];

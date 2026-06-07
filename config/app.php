<?php

declare(strict_types=1);

/**
 * Configuração geral da aplicação.
 * base_path: caminho web até a pasta onecheck (ajuste se necessário).
 * Ex.: '/onecheck' para http://localhost/onecheck/
 */
return [
    // Local XAMPP: /onecheck | Render (raiz): deixe vazio via ONECHECK_BASE_PATH=
    'base_path' => getenv('ONECHECK_BASE_PATH') !== false
        ? getenv('ONECHECK_BASE_PATH')
        : '/onecheck',
    'name'      => 'OneCheck',
];

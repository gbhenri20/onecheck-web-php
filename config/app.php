<?php

declare(strict_types=1);

/**
 * Configuração geral da aplicação.
 * base_path: caminho web até a pasta onecheck (ajuste se necessário).
 * Ex.: '/onecheck' para http://localhost/onecheck/
 */
$basePath = getenv('ONECHECK_BASE_PATH');

return [
    // Local: /onecheck | Render/produção na raiz: defina ONECHECK_BASE_PATH="" no painel
    'base_path' => $basePath !== false ? $basePath : '/onecheck',
    'name'      => 'OneCheck',
];

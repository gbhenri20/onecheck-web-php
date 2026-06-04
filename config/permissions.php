<?php

declare(strict_types=1);

return [
    'admin' => ['*'],
    'gestor' => [
        'dashboard', 'imoveis', 'vistorias', 'contratos', 'problemas', 'usuarios.view',
    ],
    'vistoriador' => [
        'dashboard', 'imoveis.view', 'vistorias', 'problemas',
    ],
    'visualizador' => [
        'dashboard', 'imoveis.view', 'vistorias.view', 'contratos.view', 'problemas.view',
    ],
    'locatario' => [
        'locatario.portal', 'locatario.checklist', 'locatario.problemas',
    ],
];

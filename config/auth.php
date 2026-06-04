<?php

declare(strict_types=1);

return [
    'jwt_secret' => getenv('ONECHECK_JWT_SECRET') ?: 'ALTERE_ESTA_CHAVE_EM_PRODUCAO_onecheck_2026',
    'access_ttl'  => 900,       // 15 minutos (RNF01)
    'refresh_ttl' => 2592000,   // 30 dias
    'mfa_pending_ttl' => 300, // 5 min para concluir MFA após senha
    'mfa_issuer'  => 'OneCheck',
    'perfis_mfa_obrigatorio' => [],
];

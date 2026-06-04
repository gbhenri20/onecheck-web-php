<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/api/bootstrap.php';

api_require_method('GET');
api_auth_user();

$stmt = Database::pdo()->query(
    'SELECT id, codigo, titulo, endereco, cidade, estado, tipo, status
     FROM imoveis
     ORDER BY codigo ASC'
);

api_ok(['imoveis' => $stmt->fetchAll()]);

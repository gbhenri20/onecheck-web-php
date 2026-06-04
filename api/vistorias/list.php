<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/api/bootstrap.php';

api_require_method('GET');
api_auth_user();

$imovelId = isset($_GET['imovel_id']) ? (int) $_GET['imovel_id'] : null;
$status = $_GET['status'] ?? null;

$sql = 'SELECT v.id, v.imovel_id, v.tipo, v.status, v.data_vistoria, v.observacoes,
               i.codigo AS imovel_codigo, i.titulo AS imovel_titulo,
               (SELECT COUNT(*) FROM vistoria_fotos f WHERE f.vistoria_id = v.id) AS total_fotos
        FROM vistorias v
        INNER JOIN imoveis i ON i.id = v.imovel_id
        WHERE 1=1';
$params = [];

if ($imovelId) {
    $sql .= ' AND v.imovel_id = ?';
    $params[] = $imovelId;
}
if ($status) {
    $sql .= ' AND v.status = ?';
    $params[] = $status;
}

$sql .= ' ORDER BY v.data_vistoria DESC, v.id DESC LIMIT 100';

$stmt = Database::pdo()->prepare($sql);
$stmt->execute($params);

api_ok(['vistorias' => $stmt->fetchAll()]);

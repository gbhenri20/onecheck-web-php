<?php

declare(strict_types=1);

/**
 * Cria vistoria no servidor (APK inicia vistoria antes de enviar fotos).
 * POST JSON: imovel_id, tipo, data_vistoria, observacoes
 */
require_once dirname(__DIR__, 2) . '/api/bootstrap.php';

api_require_method('POST');
$user = api_auth_user();

$input = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($input)) {
    $input = $_POST;
}

$imovelId = (int) ($input['imovel_id'] ?? 0);
$tipo = $input['tipo'] ?? 'entrada';
$data = $input['data_vistoria'] ?? date('Y-m-d');
$obs = trim($input['observacoes'] ?? '');

$tipos = ['entrada', 'saida', 'periodica', 'extra'];
if ($imovelId < 1 || !in_array($tipo, $tipos, true)) {
    api_error('imovel_id e tipo válidos são obrigatórios', 422);
}

$pdo = Database::pdo();
$chk = $pdo->prepare('SELECT id FROM imoveis WHERE id = ?');
$chk->execute([$imovelId]);
if (!$chk->fetch()) {
    api_error('Imóvel não encontrado', 404);
}

$stmt = $pdo->prepare(
    'INSERT INTO vistorias (imovel_id, usuario_id, tipo, status, data_vistoria, observacoes, sincronizado_mobile)
     VALUES (?, ?, ?, ?, ?, ?, 1)'
);
$stmt->execute([$imovelId, $user['id'], $tipo, 'em_andamento', $data, $obs !== '' ? $obs : null]);

api_ok([
    'vistoria_id' => (int) $pdo->lastInsertId(),
    'imovel_id'   => $imovelId,
    'tipo'        => $tipo,
    'status'      => 'em_andamento',
]);

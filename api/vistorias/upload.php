<?php

declare(strict_types=1);

/**
 * Upload de foto de cômodo — chamado pelo APK Kotlin (multipart/form-data).
 *
 * Campos:
 *   - vistoria_id (int, obrigatório)
 *   - comodo (string, obrigatório) ex: sala, cozinha, quarto_1
 *   - observacao (string, opcional)
 *   - latitude, longitude (opcional)
 *   - foto (file, obrigatório)
 *
 * Header: Authorization: Bearer {token}
 */
require_once dirname(__DIR__, 2) . '/api/bootstrap.php';

api_require_method('POST');
$user = api_auth_user();

$vistoriaId = (int) ($_POST['vistoria_id'] ?? 0);
$comodo = trim($_POST['comodo'] ?? '');
$observacao = trim($_POST['observacao'] ?? '');

if ($vistoriaId < 1 || $comodo === '') {
    api_error('vistoria_id e comodo são obrigatórios', 422);
}

if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
    api_error('Arquivo foto é obrigatório', 422);
}

$pdo = Database::pdo();
$stmt = $pdo->prepare('SELECT id, imovel_id FROM vistorias WHERE id = ? LIMIT 1');
$stmt->execute([$vistoriaId]);
$vistoria = $stmt->fetch();

if (!$vistoria) {
    api_error('Vistoria não encontrada', 404);
}

$file = $_FILES['foto'];
$allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']) ?: $file['type'];

if (!in_array($mime, $allowed, true)) {
    api_error('Tipo de imagem não permitido', 415);
}

$ext = match ($mime) {
    'image/png'  => 'png',
    'image/webp' => 'webp',
    default      => 'jpg',
};

$subdir = 'vistorias/' . $vistoriaId;
$dir = uploads_path($subdir);
$nome = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$caminhoAbs = $dir . '/' . $nome;
$caminhoRel = 'assets/uploads/' . $subdir . '/' . $nome;

if (!move_uploaded_file($file['tmp_name'], $caminhoAbs)) {
    api_error('Falha ao salvar arquivo', 500);
}

$lat = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float) $_POST['latitude'] : null;
$lng = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float) $_POST['longitude'] : null;

$insert = $pdo->prepare(
    'INSERT INTO vistoria_fotos
        (vistoria_id, comodo, arquivo_nome, arquivo_path, mime_type, tamanho_bytes, latitude, longitude, origem, observacao)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$insert->execute([
    $vistoriaId,
    $comodo,
    $nome,
    $caminhoRel,
    $mime,
    (int) $file['size'],
    $lat,
    $lng,
    'mobile',
    $observacao !== '' ? $observacao : null,
]);

$fotoId = (int) $pdo->lastInsertId();

$pdo->prepare("UPDATE vistorias SET status = 'em_andamento', sincronizado_mobile = 1, atualizado_em = NOW() WHERE id = ?")
    ->execute([$vistoriaId]);

api_ok([
    'foto_id'      => $fotoId,
    'vistoria_id'  => $vistoriaId,
    'imovel_id'    => (int) $vistoria['imovel_id'],
    'comodo'       => $comodo,
    'arquivo_url'  => base_url($caminhoRel),
    'arquivo_path' => $caminhoRel,
]);

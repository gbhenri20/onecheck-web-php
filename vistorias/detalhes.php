<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$id = get_int('id');
$pdo = Database::pdo();

$stmt = $pdo->prepare(
    'SELECT v.*, i.codigo, i.titulo, i.endereco, u.nome AS vistoriador
     FROM vistorias v
     INNER JOIN imoveis i ON i.id = v.imovel_id
     INNER JOIN usuarios u ON u.id = v.usuario_id
     WHERE v.id = ?'
);
$stmt->execute([$id]);
$v = $stmt->fetch();

if (!$v) {
    flash_set('error', 'Vistoria não encontrada.');
    redirect(base_url('vistorias/index.php'));
}

// Alterar status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] === 'status') {
        $novo = $_POST['status'] ?? '';
        $validos = ['rascunho', 'em_andamento', 'concluida', 'cancelada'];
        if (in_array($novo, $validos, true)) {
            $pdo->prepare('UPDATE vistorias SET status = ? WHERE id = ?')->execute([$novo, $id]);
            flash_set('success', 'Status atualizado.');
            redirect(base_url('vistorias/detalhes.php?id=' . $id));
        }
    }

    // Upload foto pelo painel web
    if ($_POST['acao'] === 'upload_foto' && isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $comodo = post_str('comodo');
        if ($comodo === '') {
            flash_set('error', 'Informe o cômodo.');
        } else {
            $file = $_FILES['foto'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']) ?: '';
            if (!str_starts_with($mime, 'image/')) {
                flash_set('error', 'Envie apenas imagens.');
            } else {
                $ext = $mime === 'image/png' ? 'png' : ($mime === 'image/webp' ? 'webp' : 'jpg');
                $subdir = 'vistorias/' . $id;
                $dir = uploads_path($subdir);
                $nome = date('Ymd_His') . '_web.' . $ext;
                $rel = 'assets/uploads/' . $subdir . '/' . $nome;
                if (move_uploaded_file($file['tmp_name'], $dir . '/' . $nome)) {
                    $pdo->prepare(
                        'INSERT INTO vistoria_fotos (vistoria_id, comodo, arquivo_nome, arquivo_path, mime_type, tamanho_bytes, origem)
                         VALUES (?, ?, ?, ?, ?, ?, ?)'
                    )->execute([$id, $comodo, $nome, $rel, $mime, (int) $file['size'], 'web']);
                    $pdo->prepare("UPDATE vistorias SET status = 'em_andamento' WHERE id = ? AND status = 'rascunho'")->execute([$id]);
                    flash_set('success', 'Foto adicionada ao arquivo.');
                } else {
                    flash_set('error', 'Erro ao salvar arquivo.');
                }
            }
        }
        redirect(base_url('vistorias/detalhes.php?id=' . $id));
    }
}

$fotos = $pdo->prepare('SELECT * FROM vistoria_fotos WHERE vistoria_id = ? ORDER BY comodo, criado_em DESC');
$fotos->execute([$id]);

$problemas = $pdo->prepare('SELECT * FROM problemas WHERE vistoria_id = ? ORDER BY id DESC');
$problemas->execute([$id]);

$comodosPadrao = ['sala', 'cozinha', 'quarto_1', 'quarto_2', 'banheiro', 'area_servico', 'varanda', 'garagem'];

$pageTitle = 'Vistoria #' . $id;
$activeMenu = 'vistorias';
require ONECHECK_ROOT . '/includes/header.php';
flash_render();
page_header('Vistoria #' . $id, $v['codigo'] . ' — ' . $v['titulo'],
    '<a href="' . e(base_url('vistorias/checklist.php?id=' . $id)) . '" class="btn btn-outline-secondary btn-sm">Checklist</a>'
    . '<a href="' . e(base_url('problemas/novo.php?vistoria_id=' . $id . '&imovel_id=' . $v['imovel_id'])) . '" class="btn btn-outline-danger btn-sm">Registrar problema</a>'
    . '<a href="' . e(base_url('vistorias/index.php')) . '" class="btn btn-link btn-sm">Voltar</a>');
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Informações</div>
            <div class="card-body small">
                <dl class="row mb-0">
                    <dt class="col-5">Imóvel</dt>
                    <dd class="col-7">
                        <a href="<?= e(base_url('imoveis/detalhes.php?id=' . $v['imovel_id'])) ?>"><?= e($v['codigo']) ?></a>
                    </dd>
                    <dt class="col-5">Tipo</dt><dd class="col-7"><?= e($v['tipo']) ?></dd>
                    <dt class="col-5">Data</dt><dd class="col-7"><?= format_date($v['data_vistoria']) ?></dd>
                    <dt class="col-5">Vistoriador</dt><dd class="col-7"><?= e($v['vistoriador']) ?></dd>
                    <dt class="col-5">Status</dt><dd class="col-7"><?= badge_status('vistoria', $v['status']) ?></dd>
                </dl>
                <?php if ($v['observacoes']): ?>
                <hr><p class="mb-0"><?= nl2br(e($v['observacoes'])) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Alterar status</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="acao" value="status">
                    <select name="status" class="form-select form-select-sm mb-2">
                        <?php foreach (['rascunho','em_andamento','concluida','cancelada'] as $s): ?>
                        <option value="<?= e($s) ?>" <?= $v['status'] === $s ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary btn-sm w-100">Atualizar</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Enviar foto (painel web)</div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data" class="row g-2 align-items-end">
                    <input type="hidden" name="acao" value="upload_foto">
                    <div class="col-md-4">
                        <label class="form-label small">Cômodo</label>
                        <select name="comodo" class="form-select form-select-sm" required>
                            <?php foreach ($comodosPadrao as $c): ?>
                            <option value="<?= e($c) ?>"><?= e(str_replace('_', ' ', ucfirst($c))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small">Imagem</label>
                        <input type="file" name="foto" class="form-control form-control-sm" accept="image/*" required>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary btn-sm w-100">Enviar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between">
                <span class="fw-semibold">Fotos (<?= $fotos->rowCount() ?>)</span>
                <a href="<?= e(base_url('vistorias/fotos.php?vistoria_id=' . $id)) ?>" class="small">Galeria completa</a>
            </div>
            <div class="card-body">
                <?php $listaFotos = $fotos->fetchAll(); if (!$listaFotos): ?>
                <p class="text-muted mb-0 small">Nenhuma foto. O app Kotlin pode enviar via API ou use o formulário acima.</p>
                <?php else: ?>
                <div class="row g-2">
                    <?php foreach ($listaFotos as $f): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="card photo-card h-100">
                            <a href="<?= e(base_url($f['arquivo_path'])) ?>" target="_blank">
                                <img src="<?= e(base_url($f['arquivo_path'])) ?>" alt="<?= e($f['comodo']) ?>">
                            </a>
                            <div class="card-body p-2 small">
                                <strong><?= e($f['comodo']) ?></strong>
                                <span class="badge text-bg-light text-dark float-end"><?= e($f['origem']) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Problemas desta vistoria</div>
            <ul class="list-group list-group-flush">
                <?php $pList = $problemas->fetchAll(); if (!$pList): ?>
                <li class="list-group-item text-muted small">Nenhum problema registrado.</li>
                <?php else: foreach ($pList as $p): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <a href="<?= e(base_url('problemas/detalhes.php?id=' . $p['id'])) ?>"><?= e($p['titulo']) ?></a>
                    <span><?= badge_status('problema', $p['status']) ?> <?= badge_status('prioridade', $p['prioridade']) ?></span>
                </li>
                <?php endforeach; endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

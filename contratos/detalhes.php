<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$id = get_int('id');
$pdo = Database::pdo();

$stmt = $pdo->prepare(
    'SELECT c.*, i.codigo, i.titulo, i.endereco FROM contratos c
     INNER JOIN imoveis i ON i.id = c.imovel_id WHERE c.id = ?'
);
$stmt->execute([$id]);
$c = $stmt->fetch();

if (!$c) {
    flash_set('error', 'Contrato não encontrado.');
    redirect(base_url('contratos/index.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao']) && $_POST['acao'] === 'status') {
        $novo = $_POST['status'] ?? '';
        $validos = ['rascunho', 'ativo', 'encerrado', 'cancelado'];
        if (in_array($novo, $validos, true)) {
            $pdo->prepare('UPDATE contratos SET status = ? WHERE id = ?')->execute([$novo, $id]);
            if ($novo === 'ativo') {
                $pdo->prepare("UPDATE imoveis SET status = 'ocupado' WHERE id = ?")->execute([$c['imovel_id']]);
            }
            if (in_array($novo, ['encerrado', 'cancelado'], true)) {
                $pdo->prepare("UPDATE imoveis SET status = 'disponivel' WHERE id = ?")->execute([$c['imovel_id']]);
            }
            flash_set('success', 'Status do contrato atualizado.');
            redirect(base_url('contratos/detalhes.php?id=' . $id));
        }
    }

    if (isset($_POST['acao']) && $_POST['acao'] === 'anexo' && isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
        $titulo = post_str('titulo_anexo') ?: 'Anexo';
        $tipo = $_POST['tipo_anexo'] ?? 'contrato';
        $file = $_FILES['arquivo'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'pdf';
        $subdir = 'contratos/' . $id;
        $dir = uploads_path($subdir);
        $nome = date('Ymd_His') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
        $rel = 'assets/uploads/' . $subdir . '/' . $nome;
        if (move_uploaded_file($file['tmp_name'], $dir . '/' . $nome)) {
            $pdo->prepare(
                'INSERT INTO contrato_anexos (contrato_id, titulo, arquivo_nome, arquivo_path, tipo) VALUES (?, ?, ?, ?, ?)'
            )->execute([$id, $titulo, $nome, $rel, $tipo]);
            flash_set('success', 'Anexo adicionado.');
        } else {
            flash_set('error', 'Erro ao enviar arquivo.');
        }
        redirect(base_url('contratos/detalhes.php?id=' . $id));
    }
}

$anexos = $pdo->prepare('SELECT * FROM contrato_anexos WHERE contrato_id = ? ORDER BY id DESC');
$anexos->execute([$id]);

$pageTitle = 'Contrato ' . $c['numero'];
$activeMenu = 'contratos';
require ONECHECK_ROOT . '/includes/header.php';
flash_render();
page_header('Contrato ' . $c['numero'], $c['codigo'] . ' — ' . $c['titulo'],
    '<a href="' . e(base_url('contratos/index.php')) . '" class="btn btn-link btn-sm">Voltar</a>');
?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Dados do contrato</div>
            <div class="card-body small">
                <dl class="row mb-0">
                    <dt class="col-5">Status</dt><dd class="col-7"><?= badge_status('contrato', $c['status']) ?></dd>
                    <dt class="col-5">Locatário</dt><dd class="col-7"><?= e($c['locatario_nome']) ?></dd>
                    <dt class="col-5">Documento</dt><dd class="col-7"><?= e($c['locatario_documento'] ?: '—') ?></dd>
                    <dt class="col-5">Valor</dt><dd class="col-7"><?= format_money((float) $c['valor_aluguel']) ?></dd>
                    <dt class="col-5">Período</dt>
                    <dd class="col-7"><?= format_date($c['data_inicio']) ?> — <?= format_date($c['data_fim']) ?></dd>
                    <dt class="col-5">Imóvel</dt>
                    <dd class="col-7">
                        <a href="<?= e(base_url('imoveis/detalhes.php?id=' . $c['imovel_id'])) ?>"><?= e($c['codigo']) ?></a>
                        <div class="text-muted"><?= e($c['endereco']) ?></div>
                    </dd>
                </dl>
                <?php if ($c['observacoes']): ?>
                <hr><p class="mb-0"><?= nl2br(e($c['observacoes'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Alterar status</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="acao" value="status">
                    <select name="status" class="form-select form-select-sm mb-2">
                        <?php foreach (['rascunho','ativo','encerrado','cancelado'] as $s): ?>
                        <option value="<?= e($s) ?>" <?= $c['status'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary btn-sm w-100">Atualizar</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Adicionar anexo (PDF, imagem)</div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data" class="row g-2">
                    <input type="hidden" name="acao" value="anexo">
                    <div class="col-md-5">
                        <input name="titulo_anexo" class="form-control form-control-sm" placeholder="Título do anexo" required>
                    </div>
                    <div class="col-md-3">
                        <select name="tipo_anexo" class="form-select form-select-sm">
                            <option value="contrato">Contrato</option>
                            <option value="aditivo">Aditivo</option>
                            <option value="comprovante">Comprovante</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="file" name="arquivo" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary btn-sm">Enviar anexo</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Anexos</div>
            <ul class="list-group list-group-flush">
                <?php $lista = $anexos->fetchAll(); if (!$lista): ?>
                <li class="list-group-item text-muted small">Nenhum anexo.</li>
                <?php else: foreach ($lista as $a): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold small"><?= e($a['titulo']) ?></div>
                        <div class="text-muted" style="font-size:.75rem"><?= e($a['tipo']) ?> · <?= format_datetime($a['criado_em']) ?></div>
                    </div>
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url($a['arquivo_path'])) ?>" target="_blank">Abrir</a>
                </li>
                <?php endforeach; endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

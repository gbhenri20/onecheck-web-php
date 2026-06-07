<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/includes/auth_api.php';
require_once dirname(__DIR__) . '/includes/rbac.php';
api_require_page('problemas');

if (!api_can_create('problemas')) {
    flash_set('error', 'Sem permissão para registrar problemas.');
    redirect(base_url('problemas/index.php'));
}

$erro = '';
$resContratos = ApiClient::get('/contratos', ['por_pagina' => 100, 'status' => 'ativo']);
$contratos = $resContratos['dados'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contratoId = post_str('contrato_id');
    $titulo = post_str('titulo');
    $desc = post_str('descricao');
    $prioridade = $_POST['prioridade'] ?? 'normal';

    if ($contratoId === '' || $titulo === '') {
        $erro = 'Contrato e título são obrigatórios.';
    } else {
        $res = ApiClient::post('/contratos/' . urlencode($contratoId) . '/problemas', [
            'titulo' => $titulo,
            'descricao' => $desc !== '' ? $desc : null,
            'prioridade' => $prioridade,
            'status' => 'aberto',
        ]);
        if (!empty($res['sucesso'])) {
            flash_set('success', 'Problema registrado.');
            redirect(base_url('problemas/index.php'));
        }
        $erro = $res['erro'] ?? 'Erro ao registrar problema.';
    }
}

$pageTitle = 'Novo problema';
$activeMenu = 'problemas';
require ONECHECK_ROOT . '/includes/header.php';
flash_render();
page_header('Registrar problema', '', '<a href="' . e(base_url('problemas/index.php')) . '" class="btn btn-outline-secondary btn-sm">Voltar</a>');
?>

<?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?>

<div class="card">
    <div class="card-body">
        <?php if (!$contratos): ?>
        <div class="alert alert-warning">Nenhum contrato ativo. Crie um contrato antes de registrar problemas.</div>
        <?php else: ?>
        <form method="post" class="row g-3" autocomplete="off">
            <div class="col-12">
                <label class="form-label">Contrato <span class="text-danger">*</span></label>
                <select name="contrato_id" class="form-select" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($contratos as $ct): ?>
                    <option value="<?= e($ct['id']) ?>" <?= (post_str('contrato_id') === $ct['id']) ? 'selected' : '' ?>>
                        Contrato · início <?= e(substr($ct['data_inicio'] ?? '', 0, 10)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Título <span class="text-danger">*</span></label>
                <input name="titulo" class="form-control" required placeholder="Ex: Infiltração no banheiro">
            </div>
            <div class="col-md-4">
                <label class="form-label">Prioridade</label>
                <select name="prioridade" class="form-select">
                    <?php foreach (['normal', 'alta', 'urgente'] as $pr): ?>
                    <option value="<?= e($pr) ?>"><?= e(ucfirst($pr)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Descrição</label>
                <textarea name="descricao" class="form-control" rows="4"></textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Salvar</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

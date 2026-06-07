<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/includes/auth_api.php';
require_once dirname(__DIR__) . '/includes/rbac.php';
api_require_page('locatario');

$user = api_user();
$resContratos = ApiClient::get('/contratos', ['por_pagina' => 10, 'status' => 'ativo']);
$contratos = $resContratos['dados'] ?? [];
$meuContrato = $contratos[0] ?? null;

$checklistsPendentes = [];
if ($meuContrato) {
    $resCk = ApiClient::get('/contratos/' . urlencode($meuContrato['id']) . '/checklists');
    foreach (($resCk['dados'] ?? []) as $ck) {
        if (($ck['status'] ?? '') === 'pendente_aceite') {
            $checklistsPendentes[] = $ck;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ckId = post_str('checklist_id');
    $acao = post_str('acao');
    if ($ckId !== '' && in_array($acao, ['aceitar', 'rejeitar'], true)) {
        $res = ApiClient::patch('/checklists/' . urlencode($ckId) . '/' . $acao, []);
        flash_set(!empty($res['sucesso']) ? 'success' : 'error',
            !empty($res['sucesso']) ? ($acao === 'aceitar' ? 'Vistoria aceita!' : 'Vistoria rejeitada.') : ($res['erro'] ?? 'Erro'));
        redirect(base_url('locatario/index.php'));
    }
}

$pageTitle = 'Área do locatário';
require ONECHECK_ROOT . '/locatario/_header.php';
flash_render();
?>

<div class="mb-4">
    <h1 class="h3">Olá, <?= e($user['nome'] ?? '') ?></h1>
    <p class="text-muted">Portal do locatário — vistorias, aceite e problemas.</p>
</div>

<?php if ($meuContrato): ?>
<div class="card mb-3">
    <div class="card-body">
        <h2 class="h6 fw-semibold">Seu contrato ativo</h2>
        <p class="small mb-0">
            Início: <?= e(substr($meuContrato['data_inicio'] ?? '', 0, 10)) ?>
            → Fim: <?= e(substr($meuContrato['data_fim'] ?? '', 0, 10)) ?>
        </p>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info">Nenhum contrato ativo vinculado ao seu usuário.</div>
<?php endif; ?>

<?php if ($checklistsPendentes): ?>
<div class="card mb-3 border-info">
    <div class="card-header"><i class="bi bi-clipboard-check me-1"></i>Vistorias aguardando aceite</div>
    <ul class="list-group list-group-flush">
        <?php foreach ($checklistsPendentes as $ck): ?>
        <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <strong><?= e(ucfirst($ck['tipo'] ?? 'vistoria')) ?></strong>
                <span class="text-muted small"> · <?= e(substr($ck['data_vistoria'] ?? '', 0, 10)) ?></span>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= e(base_url('vistorias/checklist.php?id=' . urlencode($ck['id']) . '&contrato_id=' . urlencode($meuContrato['id']))) ?>" class="btn btn-outline-primary btn-sm">Ver detalhes</a>
                <form method="post" class="d-inline">
                    <input type="hidden" name="checklist_id" value="<?= e($ck['id']) ?>">
                    <input type="hidden" name="acao" value="aceitar">
                    <button class="btn btn-success btn-sm">Aceitar</button>
                </form>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h6">Minhas vistorias</h3>
                <a href="<?= e(base_url('vistorias/index.php')) ?>" class="btn btn-outline-primary btn-sm">Ver vistorias</a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h6">Problemas</h3>
                <a href="<?= e(base_url('problemas/index.php')) ?>" class="btn btn-outline-primary btn-sm me-2">Ver problemas</a>
                <a href="<?= e(base_url('problemas/novo.php')) ?>" class="btn btn-primary btn-sm">Registrar</a>
            </div>
        </div>
    </div>
</div>

<?php require ONECHECK_ROOT . '/locatario/_footer.php'; ?>

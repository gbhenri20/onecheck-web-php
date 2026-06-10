<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/includes/auth_api.php';
require_once dirname(__DIR__) . '/includes/rbac.php';
require_once dirname(__DIR__) . '/includes/scope.php';
api_require_page('locatario');

$user = api_user();
$lookups = api_fetch_lookups();
$contratos = api_load_scoped_contratos(['por_pagina' => 10, 'status' => 'ativo'])['dados'] ?? [];
$meuContrato = $contratos[0] ?? null;

$checklists = api_load_scoped_checklists();
usort($checklists, fn($a, $b) => ($b['created_at'] ?? '') <=> ($a['created_at'] ?? ''));

$imovel = null;
if ($meuContrato) {
    $imovel = $lookups['imoveis'][$meuContrato['imovel_id'] ?? ''] ?? null;
}

$pageTitle = 'Área do locatário';
$portalLayout = true;
$activeMenu = 'locatario';
require ONECHECK_ROOT . '/includes/header.php';
flash_render();
?>

<div class="mb-4">
    <h1 class="h3">Olá, <?= e($user['nome'] ?? '') ?></h1>
    <p class="text-muted">Portal do locatário — acompanhe suas vistorias e problemas.</p>
</div>

<?php if ($meuContrato): ?>
<div class="card mb-3">
    <div class="card-body">
        <h2 class="h6 fw-semibold">Seu contrato ativo</h2>
        <p class="small mb-1"><strong>Imóvel:</strong> <?= e(api_imovel_label($imovel)) ?></p>
        <?php if ($imovel): ?>
        <p class="small mb-1 text-muted"><?= e(api_endereco_label($imovel['endereco'] ?? null)) ?></p>
        <p class="small mb-0 text-muted"><?= e(api_excerpt($imovel['observacoes'] ?? null, 120)) ?></p>
        <?php endif; ?>
        <p class="small mb-0 mt-2">
            Período: <?= e(substr($meuContrato['data_inicio'] ?? '', 0, 10)) ?>
            → <?= e(substr($meuContrato['data_fim'] ?? '', 0, 10)) ?>
        </p>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info">Nenhum contrato ativo vinculado ao seu usuário.</div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clipboard-check me-1"></i>Minhas vistorias</span>
        <a href="<?= e(base_url('vistorias/index.php')) ?>" class="btn btn-outline-primary btn-sm">Ver todas</a>
    </div>
    <?php if (!$checklists): ?>
    <div class="card-body text-muted small">Nenhuma vistoria registrada para você.</div>
    <?php else: ?>
    <ul class="list-group list-group-flush">
        <?php foreach (array_slice($checklists, 0, 5) as $ck):
            $vis = $lookups['usuarios'][$ck['vistoriador_id'] ?? ''] ?? null;
        ?>
        <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <strong><?= e(ucfirst($ck['tipo'] ?? 'vistoria')) ?></strong>
                <span class="text-muted small"> · <?= e(substr($ck['data_vistoria'] ?? '', 0, 10)) ?></span>
                <div class="small text-muted">Vistoriador: <?= e($vis['nome'] ?? '—') ?></div>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <?php
                $st = $ck['status'] ?? '';
                echo match($st) {
                    'pendente_aceite'  => '<span class="badge bg-info text-dark">Pendente aceite</span>',
                    'aceito'           => '<span class="badge bg-success">Aceito</span>',
                    'rejeitado'        => '<span class="badge bg-danger">Rejeitado</span>',
                    'em_preenchimento' => '<span class="badge bg-warning text-dark">Em preenchimento</span>',
                    default            => '<span class="badge bg-secondary">' . e($st) . '</span>',
                };
                ?>
                <?php if ($meuContrato): ?>
                <a href="<?= e(base_url('vistorias/checklist.php?id=' . urlencode($ck['id']) . '&contrato_id=' . urlencode($meuContrato['id']))) ?>" class="btn btn-outline-primary btn-sm">Ver detalhes</a>
                <?php endif; ?>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h6">Problemas</h3>
                <p class="small text-muted">Registre e acompanhe problemas no imóvel.</p>
                <a href="<?= e(base_url('problemas/index.php')) ?>" class="btn btn-outline-primary btn-sm me-2">Ver problemas</a>
                <a href="<?= e(base_url('problemas/novo.php')) ?>" class="btn btn-primary btn-sm">Registrar</a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h6">Meu perfil</h3>
                <p class="small text-muted">Altere seus dados ou senha de acesso.</p>
                <a href="<?= e(base_url('usuarios/perfil.php')) ?>" class="btn btn-outline-primary btn-sm">Abrir perfil</a>
            </div>
        </div>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/includes/auth_api.php';
require_once dirname(__DIR__) . '/includes/rbac.php';
api_require_page('vistorias');

flash_render();

// 1. Buscar contratos em paralelo
$resContratos = ApiClient::get('/contratos', ['por_pagina' => 100]);
$contratos    = $resContratos['dados'] ?? [];

$checklists = [];
if (!empty($contratos)) {
    // 2. Buscar checklists de todos os contratos EM PARALELO (Performance Boost)
    $requests = [];
    foreach ($contratos as $ct) {
        $requests[$ct['id']] = '/contratos/' . $ct['id'] . '/checklists';
    }
    
    $responses = ApiClient::multi_get($requests);
    
    foreach ($responses as $contratoId => $resC) {
        foreach (($resC['dados'] ?? []) as $c) {
            $c['_contrato_id'] = $contratoId;
            $checklists[] = $c;
        }
    }
}

// Ordenar por data de criação (mais recentes primeiro)
usort($checklists, fn($a, $b) => ($b['created_at'] ?? '') <=> ($a['created_at'] ?? ''));

$pageTitle  = 'Vistorias';
$activeMenu = 'vistorias';
require ONECHECK_ROOT . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="oc-page-header mb-0">
        <h2>Vistorias</h2>
        <p><?= count($checklists) ?> vistoria(s) registrada(s)</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (api_can_create('vistorias')): ?>
        <a href="<?= e(base_url('vistorias/nova.php')) ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Nova vistoria
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (!$checklists): ?>
        <div class="p-5 text-center">
            <i class="bi bi-camera" style="font-size:48px;color:var(--oc-border)"></i>
            <p class="mt-3 mb-1" style="color:var(--oc-muted)">Nenhuma vistoria registrada ainda.</p>
            <?php if (api_can_create('vistorias')): ?>
            <a href="<?= e(base_url('vistorias/nova.php')) ?>" class="btn btn-primary btn-sm mt-2">
                <i class="bi bi-plus-lg me-1"></i>Criar vistoria
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th>Data vistoria</th>
                    <th>Criado em</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($checklists as $ck): ?>
                <tr>
                    <td><span class="badge bg-primary"><?= e(ucfirst($ck['tipo'] ?? '')) ?></span></td>
                    <td>
                        <?php
                        echo match($ck['status'] ?? '') {
                            'em_preenchimento' => '<span class="badge bg-warning text-dark">Em preenchimento</span>',
                            'pendente_aceite'  => '<span class="badge bg-info text-dark">Pendente aceite</span>',
                            'aceito'           => '<span class="badge bg-success">Aceito</span>',
                            'rejeitado'        => '<span class="badge bg-danger">Rejeitado</span>',
                            'pendente_revisao' => '<span class="badge bg-warning text-dark">Pendente revisão</span>',
                            default            => '<span class="badge bg-secondary">' . e($ck['status'] ?? '') . '</span>',
                        };
                        ?>
                    </td>
                    <td style="font-size:12px"><?= e(substr($ck['data_vistoria'] ?? 'Não realizada', 0, 10)) ?></td>
                    <td style="font-size:12px;color:#6b7fa3"><?= e(substr($ck['created_at'] ?? '', 0, 10)) ?></td>
                    <td class="text-end">
                        <a href="<?= e(base_url('vistorias/checklist.php?id=' . $ck['id'] . '&contrato_id=' . $ck['_contrato_id'])) ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-eye me-1"></i>Ver
                        </a>
                        <?php if (($ck['status'] ?? '') === 'pendente_aceite' && in_array(api_role(), ['admin', 'gestor', 'locatario'], true)): ?>
                        <a href="<?= e(base_url('vistorias/checklist.php?id=' . $ck['id'] . '&contrato_id=' . $ck['_contrato_id'])) ?>" class="btn btn-success btn-sm">
                            <i class="bi bi-check-lg me-1"></i>Aceitar
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

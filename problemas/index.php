<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/includes/auth_api.php';
require_once dirname(__DIR__) . '/includes/rbac.php';
api_require_page('problemas');

// Problemas vêm vinculados a contratos — buscar todos contratos e listar problemas
// API: GET /contratos/{id}/problemas
// Por ora listamos os contratos e buscamos problemas de cada um
$resContratos = ApiClient::get('/contratos', ['por_pagina' => 100]);
$contratos    = $resContratos['dados'] ?? [];

$problemas = [];
foreach ($contratos as $ct) {
    $resP = ApiClient::get('/contratos/' . $ct['id'] . '/problemas');
    foreach (($resP['dados'] ?? []) as $p) {
        $p['_contrato_id'] = $ct['id'];
        $problemas[] = $p;
    }
}

$pageTitle  = 'Problemas';
$activeMenu = 'problemas';
require ONECHECK_ROOT . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="oc-page-header mb-0">
        <h2>Problemas</h2>
        <p><?= count($problemas) ?> problema(s) registrado(s)</p>
    </div>
    <?php if (api_can_create('problemas')): ?>
    <a href="<?= e(base_url('problemas/novo.php')) ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Novo problema
    </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (!$problemas): ?>
        <div class="p-4" style="color:#6b7fa3;font-size:13px">
            <i class="bi bi-check-circle me-2" style="color:#22c55e"></i>Nenhum problema registrado.
        </div>
        <?php else: ?>
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Prioridade</th>
                    <th>Status</th>
                    <th>Registrado em</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($problemas as $pr): ?>
                <tr>
                    <td><?= e($pr['titulo'] ?? '—') ?></td>
                    <td><span class="badge bg-secondary"><?= e($pr['prioridade'] ?? 'normal') ?></span></td>
                    <td>
                        <?php
                        echo match($pr['status'] ?? '') {
                            'aberto'       => '<span class="badge bg-danger">Aberto</span>',
                            'em_andamento' => '<span class="badge bg-warning">Em andamento</span>',
                            'resolvido'    => '<span class="badge bg-success">Resolvido</span>',
                            default        => '<span class="badge bg-secondary">' . e($pr['status'] ?? '') . '</span>',
                        };
                        ?>
                    </td>
                    <td style="font-size:12px;color:#6b7fa3"><?= e(substr($pr['created_at'] ?? '', 0, 10)) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

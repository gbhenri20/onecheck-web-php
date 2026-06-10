<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/includes/auth_api.php';
require_once dirname(__DIR__) . '/includes/rbac.php';
require_once dirname(__DIR__) . '/includes/scope.php';
api_require_page('problemas');

$lookups   = api_fetch_lookups();
$contratos = api_load_scoped_contratos()['dados'] ?? [];
$byId      = [];
foreach ($contratos as $ct) {
    $byId[$ct['id']] = $ct;
}

$problemas = [];
if ($contratos) {
    $requests = [];
    foreach ($contratos as $ct) {
        $requests[$ct['id']] = '/contratos/' . $ct['id'] . '/problemas';
    }
    foreach (ApiClient::multi_get($requests) as $contratoId => $resP) {
        foreach (($resP['dados'] ?? []) as $p) {
            $p['_contrato_id'] = $contratoId;
            $problemas[] = $p;
        }
    }
}
usort($problemas, fn($a, $b) => ($b['created_at'] ?? '') <=> ($a['created_at'] ?? ''));

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
                    <th>Descrição</th>
                    <th>Imóvel</th>
                    <th>Prioridade</th>
                    <th>Status</th>
                    <th>Registrado em</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($problemas as $pr):
                    $ct = $byId[$pr['_contrato_id'] ?? ''] ?? null;
                    $im = $ct ? ($lookups['imoveis'][$ct['imovel_id'] ?? ''] ?? null) : null;
                ?>
                <tr>
                    <td class="fw-semibold"><?= e($pr['titulo'] ?? '—') ?></td>
                    <td style="font-size:12px;max-width:200px"><?= e(api_excerpt($pr['descricao'] ?? null)) ?></td>
                    <td style="font-size:12px"><?= e(api_imovel_label($im)) ?></td>
                    <td><span class="badge bg-secondary"><?= e($pr['prioridade'] ?? 'normal') ?></span></td>
                    <td>
                        <?php
                        echo match($pr['status'] ?? '') {
                            'aberto'       => '<span class="badge bg-danger">Aberto</span>',
                            'em_andamento', 'em_analise' => '<span class="badge bg-warning text-dark">Em andamento</span>',
                            'resolvido', 'fechado' => '<span class="badge bg-success">Resolvido</span>',
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

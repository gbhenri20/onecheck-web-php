<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/includes/auth_api.php';
require_once dirname(__DIR__) . '/includes/rbac.php';
require_once dirname(__DIR__) . '/includes/scope.php';
api_require_page('contratos');

$statusF = $_GET['status'] ?? '';
$pagina  = max(1, (int)($_GET['pagina'] ?? 1));

$params = ['pagina' => $pagina, 'por_pagina' => 20];
if ($statusF !== '') $params['status'] = $statusF;

$res       = api_load_scoped_contratos($params);
$contratos = $res['dados'] ?? [];
$lookups   = api_fetch_lookups();
$total     = $res['paginacao']['total'] ?? count($contratos);
$totalPag  = max(1, (int) ceil($total / 20));

$pageTitle  = 'Contratos';
$activeMenu = 'contratos';
require ONECHECK_ROOT . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="oc-page-header mb-0">
        <h2>Contratos</h2>
        <p><?= $total ?> contrato(s) registrado(s)</p>
    </div>
    <?php if (api_can_create('contratos')): ?>
    <a href="<?= e(base_url('contratos/novo.php')) ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Novo contrato
    </a>
    <?php endif; ?>
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="ativo"     <?= $statusF==='ativo'     ? 'selected':'' ?>>Ativo</option>
                    <option value="encerrado" <?= $statusF==='encerrado' ? 'selected':'' ?>>Encerrado</option>
                    <option value="cancelado" <?= $statusF==='cancelado' ? 'selected':'' ?>>Cancelado</option>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary btn-sm">Filtrar</button>
                <a href="?" class="btn btn-outline-secondary btn-sm ms-1">Limpar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (!$contratos): ?>
        <div class="p-4" style="color:#6b7fa3;font-size:13px">
            <i class="bi bi-file-earmark-text me-2"></i>Nenhum contrato registrado.
        </div>
        <?php else: ?>
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Imóvel</th>
                    <th>Locatário</th>
                    <th>Período</th>
                    <th>Valor mensal</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contratos as $ct):
                    $im = $lookups['imoveis'][$ct['imovel_id'] ?? ''] ?? null;
                    $loc = $lookups['usuarios'][$ct['locatario_id'] ?? ''] ?? null;
                ?>
                <tr>
                    <td>
                        <div class="fw-semibold small"><?= e(api_imovel_label($im)) ?></div>
                        <div class="text-muted" style="font-size:11px"><?= e(api_excerpt($im['observacoes'] ?? null, 60)) ?></div>
                    </td>
                    <td style="font-size:12px"><?= e($loc['nome'] ?? '—') ?><br><span class="text-muted"><?= e($loc['email'] ?? '') ?></span></td>
                    <td style="font-size:12px">
                        <?= e(substr($ct['data_inicio'] ?? '', 0, 10)) ?>
                        → <?= e(substr($ct['data_fim'] ?? '', 0, 10)) ?>
                    </td>
                    <td style="font-size:12px">
                        <?= isset($ct['valor_mensal']) ? 'R$ ' . number_format((float)$ct['valor_mensal'], 2, ',', '.') : '—' ?>
                    </td>
                    <td>
                        <?php
                        echo match($ct['status'] ?? '') {
                            'ativo'     => '<span class="badge bg-success">Ativo</span>',
                            'encerrado' => '<span class="badge bg-secondary">Encerrado</span>',
                            'cancelado' => '<span class="badge bg-danger">Cancelado</span>',
                            default     => '<span class="badge bg-secondary">' . e($ct['status'] ?? '') . '</span>',
                        };
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php if ($totalPag > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-end">
        <?php for ($p = 1; $p <= $totalPag; $p++): ?>
        <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
            <a class="page-link" href="?pagina=<?= $p ?>&status=<?= e($statusF) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

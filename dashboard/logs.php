<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/includes/auth_api.php';
require_once dirname(__DIR__) . '/includes/rbac.php';
api_require_page('logs');

$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$res    = ApiClient::get('/logs', ['pagina' => $pagina, 'por_pagina' => 20]);
$logs   = $res['dados'] ?? [];
$total  = $res['paginacao']['total'] ?? 0;
$totalPag = (int) ceil($total / 20);

$pageTitle  = 'Logs';
$activeMenu = 'logs';
require ONECHECK_ROOT . '/includes/header.php';
?>

<div class="oc-page-header mb-4">
    <h2>Logs de operações</h2>
    <p><?= $total ?> registro(s) de auditoria</p>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (!$logs): ?>
        <div class="p-4" style="color:#6b7fa3;font-size:13px">
            <i class="bi bi-list-ul me-2"></i>Nenhum log registrado ainda.
        </div>
        <?php else: ?>
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Ação</th>
                    <th>Entidade</th>
                    <th>IP</th>
                    <th>Data/Hora</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td>
                        <?php
                        echo match(strtoupper($log['acao'] ?? '')) {
                            'CREATE' => '<span class="badge bg-success">CREATE</span>',
                            'UPDATE' => '<span class="badge bg-warning">UPDATE</span>',
                            'DELETE' => '<span class="badge bg-danger">DELETE</span>',
                            default  => '<span class="badge bg-secondary">' . e($log['acao'] ?? '') . '</span>',
                        };
                        ?>
                    </td>
                    <td><?= e($log['entidade'] ?? '—') ?></td>
                    <td style="font-size:12px;color:#6b7fa3"><?= e($log['ip'] ?? '—') ?></td>
                    <td style="font-size:12px;color:#6b7fa3"><?= e(substr($log['created_at'] ?? '', 0, 16)) ?></td>
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
            <a class="page-link" href="?pagina=<?= $p ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

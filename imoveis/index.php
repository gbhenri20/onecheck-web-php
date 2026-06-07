<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/includes/auth_api.php';
require_once dirname(__DIR__) . '/includes/rbac.php';
api_require_page('imoveis');

flash_render();

$statusF = $_GET['status'] ?? '';
$busca   = trim($_GET['q'] ?? '');
$pagina  = max(1, (int)($_GET['pagina'] ?? 1));

$params = ['pagina' => $pagina, 'por_pagina' => 20];
if ($statusF !== '') $params['status'] = $statusF;

$res      = ApiClient::get('/imoveis', $params);
$imoveis  = $res['dados'] ?? [];
$total    = $res['paginacao']['total'] ?? 0;
$totalPag = (int) ceil($total / 20);

$pageTitle  = 'Imóveis';
$activeMenu = 'imoveis';
require ONECHECK_ROOT . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="oc-page-header mb-0">
        <h2>Imóveis</h2>
        <p><?= $total ?> imóvel(is) cadastrado(s)</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (api_can_create('imoveis')): ?>
        <a href="<?= e(base_url('imoveis/novo.php')) ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Novo imóvel
        </a>
        <?php endif; ?>
        <a href="<?= e(base_url('imoveis/mapa.php')) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-map me-1"></i>Mapa
        </a>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body py-3">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="disponivel"  <?= $statusF==='disponivel'  ? 'selected':'' ?>>Disponível</option>
                    <option value="locado"       <?= $statusF==='locado'       ? 'selected':'' ?>>Locado</option>
                    <option value="em_vistoria"  <?= $statusF==='em_vistoria'  ? 'selected':'' ?>>Em vistoria</option>
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
        <?php if (!$imoveis): ?>
        <div class="p-5 text-center">
            <i class="bi bi-building" style="font-size:48px;color:var(--oc-border)"></i>
            <p class="mt-3 mb-1" style="color:var(--oc-muted)">Nenhum imóvel cadastrado ainda.</p>
            <?php if (api_can_create('imoveis')): ?>
            <a href="<?= e(base_url('imoveis/novo.php')) ?>" class="btn btn-primary btn-sm mt-2">
                <i class="bi bi-plus-lg me-1"></i>Criar imóvel
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipo</th>
                    <th>Tamanho</th>
                    <th>Garagem</th>
                    <th>Status</th>
                    <th>Cadastrado em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($imoveis as $im): ?>
                <tr>
                    <td style="font-family:monospace;font-weight:500"><?= e(substr((string)($im['id'] ?? ''), -5)) ?></td>
                    <td><?= e($im['tipo'] ?? '—') ?></td>
                    <td><?= e($im['tamanho'] ?? '—') ?></td>
                    <td><?= ($im['garagem'] ?? false) ? ($im['garagem_vagas'] ?? 1) . ' vaga(s)' : 'Não' ?></td>
                    <td>
                        <?php
                        echo match($im['status'] ?? '') {
                            'locado'      => '<span class="badge bg-success">Locado</span>',
                            'disponivel'  => '<span class="badge bg-primary">Disponível</span>',
                            'em_vistoria' => '<span class="badge bg-warning">Em vistoria</span>',
                            default       => '<span class="badge bg-secondary">' . e($im['status'] ?? '') . '</span>',
                        };
                        ?>
                    </td>
                    <td style="font-size:12px;color:#6b7fa3"><?= e(substr($im['created_at'] ?? '', 0, 10)) ?></td>
                    <td class="text-end">
                        <a href="<?= e(base_url('imoveis/editar.php?id=' . $im['id'])) ?>"
                           class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-pencil"></i> Editar
                        </a>
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

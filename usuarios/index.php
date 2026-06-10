<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/includes/auth_api.php';
require_once dirname(__DIR__) . '/includes/rbac.php';
require_once dirname(__DIR__) . '/includes/scope.php';
api_require_page('usuarios');

$roleF  = $_GET['role'] ?? '';
$pagina = max(1, (int)($_GET['pagina'] ?? 1));

$params = ['pagina' => $pagina, 'por_pagina' => 20];
if ($roleF !== '') $params['role'] = $roleF;

$res      = ApiClient::get('/usuarios', $params);
$usuarios = $res['dados'] ?? [];
$total    = $res['paginacao']['total'] ?? 0;
$totalPag = (int) ceil($total / 20);

$pageTitle  = 'Usuários';
$activeMenu = 'usuarios';
require ONECHECK_ROOT . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="oc-page-header mb-0">
        <h2>Usuários</h2>
        <p><?= $total ?> usuário(s) cadastrado(s)</p>
    </div>
    <?php if (api_can_create('usuarios')): ?>
    <a href="<?= e(base_url('usuarios/novo.php')) ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Novo usuário
    </a>
    <?php endif; ?>
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-md-4">
                <label class="form-label">Perfil</label>
                <select name="role" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="admin"       <?= $roleF==='admin'       ? 'selected':'' ?>>Admin</option>
                    <option value="gestor"      <?= $roleF==='gestor'      ? 'selected':'' ?>>Gestor</option>
                    <option value="vistoriador" <?= $roleF==='vistoriador' ? 'selected':'' ?>>Vistoriador</option>
                    <option value="locatario"   <?= $roleF==='locatario'   ? 'selected':'' ?>>Locatário</option>
                    <option value="visualizador"<?= $roleF==='visualizador'? 'selected':'' ?>>Visualizador</option>
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
        <?php if (!$usuarios): ?>
        <div class="p-4" style="color:#6b7fa3;font-size:13px">
            <i class="bi bi-people me-2"></i>Nenhum usuário encontrado.
        </div>
        <?php else: ?>
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>MFA</th>
                    <th>Cadastrado em</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= e($u['nome'] ?? '—') ?></td>
                    <td style="font-size:12px"><?= e($u['email'] ?? '—') ?></td>
                    <td>
                        <span class="badge bg-secondary"><?= e(api_role_label($u['role'] ?? '')) ?></span>
                    </td>
                    <td>
                        <?php if (api_mfa_ativo($u)): ?>
                            <span class="badge bg-success"><i class="bi bi-shield-check"></i> Ativo</span>
                        <?php elseif (!empty($u['mfa_enabled'])): ?>
                            <span class="badge bg-warning text-dark"><i class="bi bi-shield-exclamation"></i> Pendente</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><i class="bi bi-shield-x"></i> Não configurado</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:#6b7fa3"><?= e(substr($u['created_at'] ?? '', 0, 10)) ?></td>
                    <td class="text-end">
                        <?php if (!api_mfa_ativo($u)): ?>
                        <a href="<?= e(base_url('usuarios/mfa-configurar.php?id=' . urlencode($u['id']))) ?>"
                           class="btn btn-outline-primary btn-sm" title="Configurar MFA">
                            <i class="bi bi-shield-plus"></i>
                        </a>
                        <?php endif; ?>
                        <a href="<?= e(base_url('usuarios/editar.php?id=' . urlencode($u['id']))) ?>"
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
            <a class="page-link" href="?pagina=<?= $p ?>&role=<?= e($roleF) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

<?php
/** Menu dropdown do usuário (navbar / portal). */
$u = api_user();
if (!$u) {
    return;
}
$perfilLabel = match (api_role()) {
    'admin'       => 'Administrador',
    'gestor'      => 'Gestor',
    'vistoriador' => 'Vistoriador',
    'locatario'   => 'Locatário',
    'visualizador'=> 'Visualizador',
    default       => ucfirst(api_role()),
};
?>
<div class="dropdown">
    <button class="btn btn-outline-light btn-sm dropdown-toggle d-flex align-items-center gap-2" type="button"
            data-bs-toggle="dropdown" aria-expanded="false">
        <span class="oc-avatar oc-avatar-sm"><?= e(api_initials()) ?></span>
        <span class="d-none d-md-inline text-start" style="font-size:12px;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
            <?= e($u['nome'] ?? 'Usuário') ?>
        </span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow">
        <li class="px-3 py-2 border-bottom">
            <div class="fw-semibold small"><?= e($u['nome'] ?? '') ?></div>
            <div class="text-muted" style="font-size:11px"><?= e($u['email'] ?? '') ?></div>
            <span class="badge bg-secondary mt-1"><?= e($perfilLabel) ?></span>
        </li>
        <li><a class="dropdown-item" href="<?= e(base_url('usuarios/perfil.php')) ?>"><i class="bi bi-person me-2"></i>Visualizar perfil</a></li>
        <li><a class="dropdown-item" href="<?= e(base_url('usuarios/perfil.php#senha')) ?>"><i class="bi bi-key me-2"></i>Alterar senha</a></li>
        <?php if (api_can_access('usuarios')): ?>
        <li><a class="dropdown-item" href="<?= e(base_url('usuarios/index.php')) ?>"><i class="bi bi-people me-2"></i>Gerenciar usuários</a></li>
        <?php endif; ?>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="<?= e(base_url('public/logout.php')) ?>"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
    </ul>
</div>

<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/includes/auth_api.php';
require_once dirname(__DIR__) . '/includes/rbac.php';
require_once dirname(__DIR__) . '/includes/scope.php';
api_require_page('usuarios');

$id = get_str('id');
if ($id === '') {
    flash_set('error', 'Usuário não informado.');
    redirect(base_url('usuarios/index.php'));
}

$res = ApiClient::get('/usuarios/' . urlencode($id));
if (empty($res['sucesso']) || empty($res['dados'])) {
    flash_set('error', $res['erro'] ?? 'Usuário não encontrado.');
    redirect(base_url('usuarios/index.php'));
}
$alvo = $res['dados'];

$roles = ['admin', 'gestor', 'vistoriador', 'visualizador', 'locatario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = post_str('acao');

    if ($acao === 'remover_mfa') {
        $del = ApiClient::delete('/usuarios/' . urlencode($id) . '/mfa');
        if (!empty($del['sucesso'])) {
            flash_set('success', 'MFA removido deste usuário.');
            redirect(base_url('usuarios/editar.php?id=' . urlencode($id)));
        }
        flash_set('error', $del['erro'] ?? 'Erro ao remover MFA.');
    } else {
        $nome = post_str('nome');
        $role = $_POST['role'] ?? $alvo['role'];
        $senha = $_POST['senha'] ?? '';

        if (!in_array($role, $roles, true)) {
            flash_set('error', 'Perfil inválido.');
        } elseif ($nome === '') {
            flash_set('error', 'Nome é obrigatório.');
        } else {
            $payload = [
                'nome' => $nome,
                'role' => $role,
            ];
            if ($senha !== '') {
                $payload['senha'] = $senha;
            }
            $upd = ApiClient::put('/usuarios/' . urlencode($id), $payload);
            if (!empty($upd['sucesso'])) {
                flash_set('success', 'Usuário atualizado.');
                redirect(base_url('usuarios/index.php'));
            }
            flash_set('error', $upd['erro'] ?? 'Erro ao atualizar usuário.');
        }
    }
}

$pageTitle = 'Editar usuário';
$activeMenu = 'usuarios';
require ONECHECK_ROOT . '/includes/header.php';
flash_render();
?>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="oc-page-header mb-0">
        <h2>Editar usuário</h2>
        <p><?= e($alvo['email'] ?? '') ?></p>
    </div>
    <a href="<?= e(base_url('usuarios/index.php')) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Voltar
    </a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h3 class="h6 mb-3">Autenticação em 2 fatores (MFA)</h3>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <?php if (api_mfa_ativo($alvo)): ?>
            <span class="badge bg-success"><i class="bi bi-shield-check"></i> MFA ativo</span>
            <?php else: ?>
            <span class="badge bg-warning text-dark"><i class="bi bi-shield-x"></i> MFA não configurado</span>
            <?php endif; ?>
        </div>
        <p class="small text-muted mb-3">
            Escaneie um QR Code no app autenticador do usuário para ativar o MFA.
        </p>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= e(base_url('usuarios/mfa-configurar.php?id=' . urlencode($id))) ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-qr-code me-1"></i><?= api_mfa_ativo($alvo) ? 'Reconfigurar MFA' : 'Configurar MFA' ?>
            </a>
            <?php if (api_mfa_ativo($alvo)): ?>
            <form method="post" class="d-inline" onsubmit="return confirm('Remover MFA deste usuário?');">
                <input type="hidden" name="acao" value="remover_mfa">
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-shield-x me-1"></i>Remover MFA
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="post" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nome</label>
                <input name="nome" class="form-control" required value="<?= e($alvo['nome'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">E-mail</label>
                <input class="form-control" value="<?= e($alvo['email'] ?? '') ?>" disabled>
            </div>
            <div class="col-md-6">
                <label class="form-label">Nova senha (opcional)</label>
                <input type="password" name="senha" class="form-control" minlength="6" autocomplete="new-password">
            </div>
            <div class="col-md-6">
                <label class="form-label">Perfil</label>
                <select name="role" class="form-select">
                    <?php foreach ($roles as $r): ?>
                    <option value="<?= e($r) ?>" <?= ($alvo['role'] ?? '') === $r ? 'selected' : '' ?>><?= e(api_role_label($r)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <button class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

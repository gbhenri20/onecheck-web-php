<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/includes/auth_api.php';
require_once dirname(__DIR__) . '/includes/rbac.php';
require_once dirname(__DIR__) . '/includes/scope.php';
api_require_login();

$res = ApiClient::get('/usuarios/me');
if (empty($res['sucesso']) || empty($res['dados'])) {
    flash_set('error', $res['erro'] ?? 'Não foi possível carregar seu perfil.');
    redirect(api_home_url());
}
$dados = $res['dados'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = post_str('nome');
    $senhaAtual = $_POST['senha_atual'] ?? '';
    $senhaNova = $_POST['senha_nova'] ?? '';

    $payload = ['nome' => $nome];
    if ($senhaNova !== '') {
        $payload['senha_atual'] = $senhaAtual;
        $payload['senha_nova'] = $senhaNova;
    }

    $upd = ApiClient::patch('/usuarios/me', $payload);
    if (!empty($upd['sucesso'])) {
        $_SESSION['user']['nome'] = $upd['dados']['nome'] ?? $nome;
        flash_set('success', 'Perfil atualizado.');
        redirect(base_url('usuarios/perfil.php'));
    }
    flash_set('error', $upd['erro'] ?? 'Erro ao atualizar perfil.');
}

$pageTitle = 'Meu perfil';
$activeMenu = '';
require ONECHECK_ROOT . '/includes/header.php';
flash_render();
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">Meu perfil</h2>
                <p class="mb-3">
                    <span class="badge bg-secondary"><?= e(api_role_label($dados['role'] ?? '')) ?></span>
                    <?php if (api_mfa_ativo($dados)): ?>
                    <span class="badge bg-success"><i class="bi bi-shield-check"></i> MFA ativo</span>
                    <?php else: ?>
                    <span class="badge bg-warning text-dark">MFA inativo</span>
                    <?php endif; ?>
                </p>
                <p class="small text-muted mb-3">E-mail: <?= e($dados['email'] ?? '') ?></p>

                <div class="card bg-dark border-secondary mb-4">
                    <div class="card-body py-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <strong class="small">Autenticação em 2 fatores (MFA)</strong>
                                <p class="small text-muted mb-0">
                                    <?php if (api_mfa_ativo($dados)): ?>
                                    Proteção ativa no login com Google Authenticator / Authy.
                                    <?php else: ?>
                                    Configure o MFA para aumentar a segurança da sua conta.
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?php if (api_mfa_ativo($dados)): ?>
                            <span class="badge bg-success"><i class="bi bi-shield-check"></i> Ativo</span>
                            <?php else: ?>
                            <a href="<?= e(base_url('usuarios/mfa-configurar.php')) ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-qr-code me-1"></i>Configurar MFA
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <form method="post" class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Nome</label>
                        <input name="nome" class="form-control" required value="<?= e($dados['nome'] ?? '') ?>">
                    </div>
                    <div class="col-12" id="senha"><hr><p class="small text-muted mb-0">Alterar senha (opcional)</p></div>
                    <div class="col-md-6">
                        <label class="form-label">Senha atual</label>
                        <input type="password" name="senha_atual" class="form-control" autocomplete="current-password">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nova senha</label>
                        <input type="password" name="senha_nova" class="form-control" minlength="6" autocomplete="new-password">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Salvar</button>
                        <a href="<?= e(api_home_url()) ?>" class="btn btn-link">Voltar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

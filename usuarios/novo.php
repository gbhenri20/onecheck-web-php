<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/includes/auth_api.php';
api_require_login();

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = trim($_POST['nome']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $role  = trim($_POST['role']  ?? '');
    $cpf   = preg_replace('/\D/', '', $_POST['cpf'] ?? '');

    if (!$nome || !$email || !$senha || !$role) {
        $erro = 'Nome, e-mail, senha e perfil são obrigatórios.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } else {
        $payload = ['nome' => $nome, 'email' => $email, 'senha' => $senha, 'role' => $role];
        if ($cpf !== '') $payload['cpf'] = $cpf;

        $res = ApiClient::post('/usuarios', $payload);

        if (!empty($res['sucesso'])) {
            redirect(base_url('usuarios/index.php'));
        } else {
            $erro = $res['erro'] ?? 'Erro ao cadastrar usuário.';
        }
    }
}

$pageTitle  = 'Novo usuário';
$activeMenu = 'usuarios';
require ONECHECK_ROOT . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="oc-page-header mb-0">
        <h2>Novo usuário</h2>
        <p>Cadastre um novo acesso ao sistema</p>
    </div>
    <a href="<?= e(base_url('usuarios/index.php')) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Voltar
    </a>
</div>

<?php if ($erro): ?>
<div class="alert alert-danger mb-3"><i class="bi bi-exclamation-triangle me-2"></i><?= e($erro) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" autocomplete="off">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nome completo <span style="color:#f87171">*</span></label>
                    <input type="text" name="nome" class="form-control" required
                           placeholder="João da Silva" value="<?= e($_POST['nome'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">E-mail <span style="color:#f87171">*</span></label>
                    <input type="email" name="email" class="form-control" required
                           placeholder="joao@email.com" value="<?= e($_POST['email'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Senha <span style="color:#f87171">*</span></label>
                    <input type="password" name="senha" class="form-control" required
                           placeholder="Mínimo 6 caracteres">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Perfil <span style="color:#f87171">*</span></label>
                    <select name="role" class="form-select" required>
                        <option value="">Selecione...</option>
                        <option value="admin"       <?= ($_POST['role'] ?? '')==='admin'       ? 'selected':'' ?>>Administrador</option>
                        <option value="vistoriador" <?= ($_POST['role'] ?? '')==='vistoriador' ? 'selected':'' ?>>Vistoriador</option>
                        <option value="locatario"   <?= ($_POST['role'] ?? '')==='locatario'   ? 'selected':'' ?>>Locatário</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">CPF</label>
                    <input type="text" name="cpf" class="form-control"
                           placeholder="000.000.000-00" maxlength="14" value="<?= e($_POST['cpf'] ?? '') ?>">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Cadastrar usuário
                </button>
                <a href="<?= e(base_url('usuarios/index.php')) ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

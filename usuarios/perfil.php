<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$user = Auth::user();
$pdo = Database::pdo();
$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
$stmt->execute([$user['id']]);
$dados = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = post_str('nome');
    $senhaAtual = $_POST['senha_atual'] ?? '';
    $senhaNova = $_POST['senha_nova'] ?? '';

    if ($nome === '') {
        flash_set('error', 'Nome é obrigatório.');
    } elseif ($senhaNova !== '' && !password_verify($senhaAtual, $dados['senha_hash'])) {
        flash_set('error', 'Senha atual incorreta.');
    } else {
        if ($senhaNova !== '') {
            $pdo->prepare('UPDATE usuarios SET nome=?, senha_hash=? WHERE id=?')
                ->execute([$nome, password_hash($senhaNova, PASSWORD_DEFAULT), $user['id']]);
        } else {
            $pdo->prepare('UPDATE usuarios SET nome=? WHERE id=?')->execute([$nome, $user['id']]);
        }
        $_SESSION['user']['nome'] = $nome;
        flash_set('success', 'Perfil atualizado.');
        redirect(base_url('usuarios/perfil.php'));
    }
}

$pageTitle = 'Meu perfil';
$activeMenu = 'usuarios';
require ONECHECK_ROOT . '/includes/header.php';
flash_render();
page_header('Meu perfil', $dados['email']);
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <p class="mb-3">
                    <?= badge_status('perfil', $dados['perfil']) ?>
                    <?php if ((int) ($dados['mfa_enabled'] ?? 0)): ?>
                    <span class="badge text-bg-success">MFA ativo</span>
                    <?php else: ?>
                    <span class="badge text-bg-warning text-dark">MFA não configurado</span>
                    <?php endif; ?>
                </p>
                <?php if (!(int) ($dados['mfa_enabled'] ?? 0)): ?>
                <p><a href="<?= e(base_url('public/mfa-setup.php')) ?>" class="btn btn-outline-primary btn-sm">Configurar autenticação em 2 fatores</a></p>
                <?php endif; ?>
                <form method="post" class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Nome</label>
                        <input name="nome" class="form-control" required value="<?= e($dados['nome']) ?>">
                    </div>
                    <div class="col-12"><hr><p class="small text-muted mb-0">Alterar senha (opcional)</p></div>
                    <div class="col-md-6">
                        <label class="form-label">Senha atual</label>
                        <input type="password" name="senha_atual" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nova senha</label>
                        <input type="password" name="senha_nova" class="form-control" minlength="6">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Salvar</button>
                        <a href="<?= e(Auth::homeUrl()) ?>" class="btn btn-link">Voltar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

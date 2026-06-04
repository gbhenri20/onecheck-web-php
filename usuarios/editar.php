<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireRole('admin');

$id = get_int('id');
$stmt = Database::pdo()->prepare('SELECT * FROM usuarios WHERE id = ?');
$stmt->execute([$id]);
$alvo = $stmt->fetch();

if (!$alvo) {
    flash_set('error', 'Usuário não encontrado.');
    redirect(base_url('usuarios/index.php'));
}

$perfis = ['admin', 'gestor', 'vistoriador', 'visualizador', 'locatario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = post_str('nome');
    $perfil = $_POST['perfil'] ?? 'vistoriador';
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $mfaObr = isset($_POST['mfa_obrigatorio']) ? 1 : 0;
    $senha = $_POST['senha'] ?? '';

    if (!in_array($perfil, $perfis, true)) {
        flash_set('error', 'Perfil inválido.');
    } elseif ($nome === '') {
        flash_set('error', 'Nome é obrigatório.');
    } else {
        if (Mfa::isMandatoryForProfile($perfil)) {
            $mfaObr = 1;
        }
        if ($senha !== '') {
            Database::pdo()->prepare(
                'UPDATE usuarios SET nome=?, perfil=?, ativo=?, mfa_obrigatorio=?, senha_hash=? WHERE id=?'
            )->execute([$nome, $perfil, $ativo, $mfaObr, password_hash($senha, PASSWORD_DEFAULT), $id]);
        } else {
            Database::pdo()->prepare(
                'UPDATE usuarios SET nome=?, perfil=?, ativo=?, mfa_obrigatorio=? WHERE id=?'
            )->execute([$nome, $perfil, $ativo, $mfaObr, $id]);
        }
        AuditLog::record('update', 'usuarios', (string) $id);
        flash_set('success', 'Usuário atualizado.');
        redirect(base_url('usuarios/index.php'));
    }
}

$pageTitle = 'Editar usuário';
$activeMenu = 'usuarios';
require ONECHECK_ROOT . '/includes/header.php';
flash_render();
page_header('Editar ' . $alvo['nome'], $alvo['email']);
?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <p class="small text-muted mb-3">
            MFA: <?= (int) ($alvo['mfa_enabled'] ?? 0) ? '<span class="badge text-bg-success">Ativo</span>' : '<span class="badge text-bg-secondary">Inativo</span>' ?>
        </p>
        <form method="post" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nome</label>
                <input name="nome" class="form-control" required value="<?= e($alvo['nome']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">E-mail</label>
                <input class="form-control" value="<?= e($alvo['email']) ?>" disabled>
            </div>
            <div class="col-md-4">
                <label class="form-label">Nova senha (opcional)</label>
                <input type="password" name="senha" class="form-control" minlength="6">
            </div>
            <div class="col-md-4">
                <label class="form-label">Perfil</label>
                <select name="perfil" class="form-select">
                    <?php foreach ($perfis as $pr): ?>
                    <option value="<?= e($pr) ?>" <?= $alvo['perfil'] === $pr ? 'selected' : '' ?>><?= e(ucfirst($pr)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label d-block">&nbsp;</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="ativo" id="ativo" <?= (int) $alvo['ativo'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="ativo">Ativo</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="mfa_obrigatorio" id="mfa_obr"
                        <?= (int) ($alvo['mfa_obrigatorio'] ?? 0) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="mfa_obr">MFA obrigatório</label>
                </div>
            </div>
            <div class="col-12">
                <button class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

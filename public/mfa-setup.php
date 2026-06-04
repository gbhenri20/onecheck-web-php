<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pending = Auth::mfaPending();
$loggedIn = Auth::check();

if (!$pending && !$loggedIn) {
    redirect(base_url('public/login.php'));
}

if ($pending && $pending['mode'] !== 'setup') {
    redirect(base_url('public/mfa-verify.php'));
}

$userId = $pending ? (int) $pending['user_id'] : (int) Auth::user()['id'];
$user = Auth::fetchUserById($userId);

if (!$user) {
    redirect(base_url('public/login.php'));
}

$erro = '';
$secret = $_SESSION['mfa_setup_secret'] ?? Mfa::generateSecret();
$_SESSION['mfa_setup_secret'] = $secret;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    if (Auth::enableMfa($userId, $secret, $code)) {
        unset($_SESSION['mfa_setup_secret']);
        if ($pending) {
            Auth::completeWebLogin($user);
            flash_set('success', 'MFA ativado com sucesso.');
            redirect(Auth::homeUrl());
        }
        flash_set('success', 'MFA ativado.');
        redirect(base_url('usuarios/perfil.php'));
    }
    $erro = 'Código inválido. Confira o app autenticador e tente novamente.';
}

$qrUrl = Mfa::qrCodeUrl($secret, $user['email']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configurar MFA · OneCheck</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(asset_url('css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="login-wrap">
    <div class="card shadow-lg border-0" style="width: 100%; max-width: 480px;">
        <div class="card-body p-4 p-md-5">
            <h1 class="h4 mb-1 text-center">Configurar MFA</h1>
            <p class="text-muted text-center small mb-4">
                Escaneie o QR Code no Google Authenticator ou Authy e informe o código gerado.
            </p>

            <?php if ($pending): ?>
            <div class="alert alert-warning small py-2">
                MFA é obrigatório para seu perfil (<?= e($user['perfil']) ?>).
            </div>
            <?php endif; ?>

            <?php if ($erro): ?>
            <div class="alert alert-danger py-2"><?= e($erro) ?></div>
            <?php endif; ?>

            <div class="text-center mb-3">
                <img src="<?= e($qrUrl) ?>" alt="QR Code MFA" width="200" height="200" class="border rounded">
            </div>
            <p class="small text-muted text-center mb-3">
                Chave manual: <code><?= e($secret) ?></code>
            </p>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label" for="code">Código de verificação</label>
                    <input type="text" class="form-control text-center" id="code" name="code"
                           inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Ativar MFA</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>

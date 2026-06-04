<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';

// Já logado
if (!empty($_SESSION['api_token'])) {
    redirect(base_url('dashboard/index.php'));
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Informe e-mail e senha.';
    } else {
        $res = ApiClient::post('/auth/login', ['email' => $email, 'senha' => $senha]);

        if (!empty($res['sucesso']) && !empty($res['dados']['access_token'])) {
            // Login direto sem MFA
            $_SESSION['api_token']         = $res['dados']['access_token'];
            $_SESSION['api_refresh_token'] = $res['dados']['refresh_token'];
            $_SESSION['user']              = $res['dados']['usuario'];
            redirect(base_url('dashboard/index.php'));

        } elseif (!empty($res['dados']['mfa_required'])) {
            // MFA necessário
            $_SESSION['mfa_temp_token'] = $res['dados']['temp_token'];
            redirect(base_url('public/mfa-verify.php'));

        } else {
            $erro = $res['erro'] ?? 'Credenciais inválidas.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · OneCheck</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset_url('css/style.css')) ?>" rel="stylesheet">
    <style>
        .brand-one   { color: #ffffff; }
        .brand-check { color: #22c55e; }
    </style>
</head>
<body class="app-body">
<div class="login-wrap">
    <div class="login-card">
        <div class="login-logo">
            <span style="font-size:28px;font-weight:700;letter-spacing:-.5px">
                <span class="brand-one">One</span><span class="brand-check">Check</span>
            </span>
        </div>
        <p style="text-align:center;font-size:13px;color:#6b7fa3;margin-bottom:28px">
            Sistema de gestão de imóveis
        </p>

        <?php if ($erro): ?>
        <div class="alert alert-danger py-2 mb-3"><?= e($erro) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="mb-3">
                <label class="form-label" for="email"><i class="bi bi-envelope me-1"></i>E-mail</label>
                <input type="email" class="form-control" id="email" name="email"
                       required value="<?= e($_POST['email'] ?? '') ?>" placeholder="seu@email.com">
            </div>
            <div class="mb-4">
                <label class="form-label" for="senha"><i class="bi bi-lock me-1"></i>Senha</label>
                <input type="password" class="form-control" id="senha" name="senha"
                       required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary w-100" style="padding:10px;font-size:14px">
                Entrar <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </form>
        <p style="font-size:11px;color:#4a5568;text-align:center;margin-top:20px;margin-bottom:0">
            Admin e vistoriador: MFA obrigatório após o primeiro acesso.
        </p>
    </div>
</div>
</body>
</html>

<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';

if (!empty($_SESSION['api_token'])) redirect(base_url('dashboard/index.php'));
if (empty($_SESSION['mfa_temp_token'])) redirect(base_url('public/login.php'));

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['code'] ?? '');
    $res = ApiClient::post('/auth/mfa/verify', [
        'temp_token' => $_SESSION['mfa_temp_token'],
        'codigo'     => $codigo,
    ]);

    if (!empty($res['sucesso']) && !empty($res['dados']['access_token'])) {
        unset($_SESSION['mfa_temp_token']);
        $_SESSION['api_token']         = $res['dados']['access_token'];
        $_SESSION['api_refresh_token'] = $res['dados']['refresh_token'];
        $_SESSION['user']              = $res['dados']['usuario'];
        redirect(base_url('dashboard/index.php'));
    } else {
        $erro = 'Código inválido. Tente novamente.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificação MFA · OneCheck</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset_url('css/style.css')) ?>" rel="stylesheet">
    <style>.brand-one{color:#fff}.brand-check{color:#22c55e}</style>
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
            <i class="bi bi-shield-lock me-1"></i>Verificação em dois fatores
        </p>
        <?php if ($erro): ?>
        <div class="alert alert-danger py-2 mb-3"><?= e($erro) ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <div class="mb-4">
                <label class="form-label" for="code"><i class="bi bi-key me-1"></i>Código de 6 dígitos</label>
                <input type="text" class="form-control form-control-lg text-center" id="code" name="code"
                       inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus placeholder="000000">
            </div>
            <button type="submit" class="btn btn-primary w-100" style="padding:10px">Verificar</button>
        </form>
        <p style="text-align:center;margin-top:16px;margin-bottom:0">
            <a href="<?= e(base_url('public/login.php')) ?>" style="font-size:12px">← Voltar ao login</a>
        </p>
    </div>
</div>
</body></html>

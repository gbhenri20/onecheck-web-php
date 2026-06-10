<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/includes/api_jwt.php';

// Já logado
if (!empty($_SESSION['api_token'])) {
    require_once dirname(__DIR__) . '/includes/rbac.php';
    redirect(api_home_url());
}

$logoutMsg = isset($_GET['logout']);
$erro = '';

/**
 * @return never
 */
function login_complete(array $res): void
{
    $_SESSION['api_token']         = $res['dados']['access_token'];
    $_SESSION['api_refresh_token'] = $res['dados']['refresh_token'] ?? null;
    $_SESSION['user']              = $res['dados']['usuario'];
    require_once ONECHECK_ROOT . '/includes/rbac.php';
    redirect(api_home_url());
}

/**
 * @return never
 */
function login_redirect_mfa(string $tempToken): void
{
    $_SESSION['mfa_temp_token'] = $tempToken;
    redirect(base_url('public/mfa-verify.php'));
}

/**
 * Detecta MFA ativo consultando /usuarios/me (funciona mesmo com API de login desatualizada).
 */
function login_check_mfa_ativo(string $accessToken): array
{
    $_SESSION['api_token'] = $accessToken;
    $me = ApiClient::get('/usuarios/me');
    unset($_SESSION['api_token'], $_SESSION['api_refresh_token']);

    if (empty($me['sucesso']) || empty($me['dados'])) {
        return ['ativo' => false, 'user_id' => ''];
    }

    $d = $me['dados'];
    $ativo = !empty($d['mfa_ativo'])
        || (!empty($d['mfa_configurado']) && !empty($d['mfa_enabled']));

    return ['ativo' => $ativo, 'user_id' => (string) ($d['id'] ?? '')];
}

/**
 * @return never
 */
function login_force_mfa(string $userId): void
{
    if ($userId === '') {
        throw new RuntimeException('Usuário sem ID para MFA.');
    }
    if (!api_jwt_configured()) {
        throw new RuntimeException(
            'Configure ONECHECK_JWT_SECRET no servidor web com o mesmo valor de JWT_SECRET da API (Render → onecheck-api → Environment).'
        );
    }
    login_redirect_mfa(api_issue_mfa_temp_token($userId));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Informe e-mail e senha.';
    } else {
        $res = ApiClient::post('/auth/login', ['email' => $email, 'senha' => $senha]);
        $dados = $res['dados'] ?? [];
        $tempToken = (string) ($dados['temp_token'] ?? $dados['mfa_token'] ?? '');

        try {
            if (!empty($res['sucesso']) && !empty($dados['mfa_required']) && $tempToken !== '') {
                login_redirect_mfa($tempToken);

            } elseif (!empty($res['sucesso']) && !empty($dados['access_token'])) {
                $userId = (string) ($dados['usuario']['id'] ?? '');
                $mfa = login_check_mfa_ativo($dados['access_token']);
                if ($mfa['user_id'] !== '') {
                    $userId = $mfa['user_id'];
                }

                if ($mfa['ativo']) {
                    login_force_mfa($userId);
                }

                login_complete($res);

            } else {
                $erro = $res['erro'] ?? 'Credenciais inválidas.';
            }
        } catch (RuntimeException $e) {
            $erro = $e->getMessage();
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
        <p style="text-align:center;font-size:14px;color:#94A3B8;margin-bottom:32px">
            Sistema de gestão de imóveis
        </p>

        <?php if ($logoutMsg): ?>
        <div class="alert alert-success py-2 mb-3">Você saiu com sucesso.</div>
        <?php endif; ?>
        <?php if ($erro): ?>
        <div class="alert alert-danger py-2 mb-3"><?= e($erro) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="mb-3">
                <label class="form-label" for="email"><i class="bi bi-envelope me-1"></i>E-mail</label>
                <input type="email" class="form-control" id="email" name="email"
                       required autocomplete="off" value="" placeholder="seu@email.com">
            </div>
            <div class="mb-4">
                <label class="form-label" for="senha"><i class="bi bi-lock me-1"></i>Senha</label>
                <input type="password" class="form-control" id="senha" name="senha"
                       required autocomplete="new-password" placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary w-100" style="padding:14px;font-size:16px;font-weight:600;border-radius:12px;min-height:52px">
                Entrar <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </form>
        <p style="font-size:11px;color:#4a5568;text-align:center;margin-top:20px;margin-bottom:0">
            Contas com MFA: após a senha, informe o código do autenticador.
        </p>
    </div>
</div>
</body>
</html>

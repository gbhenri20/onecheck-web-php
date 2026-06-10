<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/includes/auth_api.php';
require_once dirname(__DIR__) . '/includes/rbac.php';
require_once dirname(__DIR__) . '/includes/scope.php';
api_require_login();

$targetId = get_str('id');
$isAdminSetup = $targetId !== '';

if ($isAdminSetup) {
    api_require_page('usuarios');
    $resUser = ApiClient::get('/usuarios/' . urlencode($targetId));
    if (empty($resUser['sucesso']) || empty($resUser['dados'])) {
        flash_set('error', $resUser['erro'] ?? 'Usuário não encontrado.');
        redirect(base_url('usuarios/index.php'));
    }
    $alvo = $resUser['dados'];
    $setupPath = '/usuarios/' . urlencode($targetId) . '/mfa/setup';
    $enablePath = '/usuarios/' . urlencode($targetId) . '/mfa/enable';
    $voltarUrl = base_url('usuarios/editar.php?id=' . urlencode($targetId));
    $pageTitle = 'Configurar MFA — ' . ($alvo['nome'] ?? '');
} else {
    $resUser = ApiClient::get('/usuarios/me');
    if (empty($resUser['sucesso']) || empty($resUser['dados'])) {
        flash_set('error', $resUser['erro'] ?? 'Não foi possível carregar seu perfil.');
        redirect(api_home_url());
    }
    $alvo = $resUser['dados'];
    $setupPath = '/usuarios/me/mfa/setup';
    $enablePath = '/usuarios/me/mfa/enable';
    $voltarUrl = base_url('usuarios/perfil.php');
    $pageTitle = 'Configurar MFA';
}

$erro = '';
$setup = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = post_str('acao');
    if ($acao === 'gerar') {
        $resSetup = ApiClient::post($setupPath, []);
        if (!empty($resSetup['sucesso'])) {
            $_SESSION['mfa_setup_pending'] = [
                'secret' => $resSetup['dados']['secret'] ?? '',
                'target_id' => $targetId,
            ];
        } else {
            $erro = $resSetup['erro'] ?? 'Erro ao gerar código MFA.';
        }
    } elseif ($acao === 'ativar') {
        $secret = $_SESSION['mfa_setup_pending']['secret'] ?? post_str('secret');
        $codigo = preg_replace('/\D/', '', $_POST['codigo'] ?? '') ?? '';
        if ($secret === '' || strlen($codigo) !== 6) {
            $erro = 'Informe o código de 6 dígitos do autenticador.';
        } else {
            $resEnable = ApiClient::post($enablePath, [
                'secret' => $secret,
                'codigo' => $codigo,
            ]);
            if (!empty($resEnable['sucesso'])) {
                unset($_SESSION['mfa_setup_pending']);
                flash_set('success', 'MFA ativado com sucesso para ' . ($alvo['nome'] ?? 'usuário') . '.');
                redirect($voltarUrl);
            }
            $erro = $resEnable['erro'] ?? 'Código inválido. Confira o app autenticador.';
        }
    }
}

$pending = $_SESSION['mfa_setup_pending'] ?? null;
if ($pending && ($pending['target_id'] ?? '') === $targetId && !empty($pending['secret'])) {
    $secret = $pending['secret'];
    $cfg = require ONECHECK_ROOT . '/config/auth.php';
    $issuer = rawurlencode($cfg['mfa_issuer']);
    $label = rawurlencode($cfg['mfa_issuer'] . ':' . ($alvo['email'] ?? ''));
    $setup = [
        'secret' => $secret,
        'qr_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data='
            . rawurlencode("otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}"),
    ];
}

$activeMenu = $isAdminSetup ? 'usuarios' : '';
require ONECHECK_ROOT . '/includes/header.php';
flash_render();
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h2 class="h5 mb-1">Configurar autenticação em 2 fatores</h2>
                        <p class="text-muted small mb-0"><?= e($alvo['email'] ?? '') ?></p>
                    </div>
                    <a href="<?= e($voltarUrl) ?>" class="btn btn-outline-secondary btn-sm">Voltar</a>
                </div>

                <?php if (api_mfa_ativo($alvo)): ?>
                <div class="alert alert-success small">
                    <i class="bi bi-shield-check me-1"></i>Este usuário já possui MFA ativo.
                </div>
                <?php endif; ?>

                <?php if ($erro): ?>
                <div class="alert alert-danger py-2"><?= e($erro) ?></div>
                <?php endif; ?>

                <?php if (!$setup): ?>
                <p class="small text-muted">
                    Gere um QR Code para escanear no Google Authenticator, Authy ou app similar.
                    <?php if ($isAdminSetup): ?>
                    O usuário pode escanear este código ou você pode informar o código gerado pelo app dele.
                    <?php endif; ?>
                </p>
                <form method="post">
                    <input type="hidden" name="acao" value="gerar">
                    <button class="btn btn-primary">
                        <i class="bi bi-qr-code me-1"></i>Gerar QR Code MFA
                    </button>
                </form>
                <?php else: ?>
                <p class="small text-muted mb-3">
                    Escaneie o QR Code abaixo e informe o código de 6 dígitos exibido no app.
                </p>
                <div class="text-center mb-3">
                    <img src="<?= e($setup['qr_url']) ?>" alt="QR Code MFA" width="200" height="200" class="border rounded">
                </div>
                <p class="small text-muted text-center mb-3">
                    Chave manual: <code><?= e($setup['secret']) ?></code>
                </p>
                <form method="post" class="mb-2">
                    <input type="hidden" name="acao" value="ativar">
                    <input type="hidden" name="secret" value="<?= e($setup['secret']) ?>">
                    <div class="mb-3">
                        <label class="form-label" for="codigo">Código de verificação</label>
                        <input type="text" class="form-control text-center" id="codigo" name="codigo"
                               inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary">Ativar MFA</button>
                </form>
                <form method="post">
                    <input type="hidden" name="acao" value="gerar">
                    <button type="submit" class="btn btn-link btn-sm px-0">Gerar novo QR Code</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/includes/auth_api.php';
api_require_login();

$id = trim($_GET['id'] ?? '');
if (!$id) redirect(base_url('imoveis/index.php'));

$res    = ApiClient::get('/imoveis/' . $id);
$imovel = $res['dados'] ?? null;
if (!$imovel) redirect(base_url('imoveis/index.php'));

$erro = '';
$resEnd = ApiClient::get('/imoveis/' . $id . '/endereco');
$end = !empty($resEnd['sucesso']) ? ($resEnd['dados'] ?? []) : [];
if (!$end) {
    $endRaw = $imovel['endereco'] ?? [];
    $end = is_array($endRaw) ? $endRaw : ['rua' => $endRaw];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo      = trim($_POST['codigo'] ?? '');
    $titulo      = trim($_POST['titulo'] ?? '');
    $tipo        = trim($_POST['tipo'] ?? '');
    $tam         = trim($_POST['tamanho_num'] ?? '');
    $garagem     = ($_POST['garagem'] ?? 'nenhuma') === 'sim';
    $vagas       = (int)($_POST['garagem_vagas'] ?? 1);
    $status      = trim($_POST['status'] ?? 'disponivel');
    $observacoes = trim($_POST['observacoes'] ?? '');

    if (!$titulo || !$tipo || !$tam) {
        $erro = 'Título, tipo e tamanho são obrigatórios.';
    } else {
        $payload = [
            'codigo'        => $codigo,
            'titulo'        => $titulo,
            'tipo'          => $tipo,
            'tamanho'       => $tam . 'm²',
            'garagem'       => $garagem,
            'garagem_vagas' => $vagas,
            'status'        => $status,
            'observacoes'   => $observacoes,
        ];

        $resUpd = ApiClient::put('/imoveis/' . $id, $payload);
        if (!empty($resUpd['sucesso'])) {
            $endPayload = endereco_payload_from_post();
            if ($endPayload !== null) {
                ApiClient::post('/imoveis/' . $id . '/endereco', $endPayload);
            }
            redirect(base_url('imoveis/index.php'));
        } else {
            $erro = $resUpd['erro'] ?? 'Erro ao atualizar.';
        }
    }
}

$pageTitle  = 'Editar imóvel';
$activeMenu = 'imoveis';
require ONECHECK_ROOT . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="oc-page-header mb-0">
        <h2>Editar Imóvel</h2>
        <p>Atualize os dados do imóvel selecionado</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(base_url('imoveis/index.php')) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
    </div>
</div>

<?php if ($erro): ?>
    <div class="alert alert-danger mb-3"><?= e($erro) ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <form id="editar-imovel-form" method="post" autocomplete="off">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="mb-0" style="color:#6b7fa3;font-size:11px;text-transform:uppercase;letter-spacing:.06em">Edição de dados</h6>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-check-lg me-1"></i>Salvar Alterações
                </button>
            </div>
            
            <?php 
            $modo = 'editar';
            require '_form_campos.php'; 
            ?>

            <div class="mt-4 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Salvar Alterações
                </button>
                <a href="<?= e(base_url('imoveis/index.php')) ?>" class="btn btn-outline-secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

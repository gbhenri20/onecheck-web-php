<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/includes/auth_api.php';
api_require_login();

$erro = '';

// Buscar imóveis disponíveis para o select
$resImoveis = ApiClient::get('/imoveis', ['status' => 'disponivel', 'por_pagina' => 100]);
$imoveis    = $resImoveis['dados'] ?? [];

// Buscar usuários locatários para o select
$resLocatarios = ApiClient::get('/usuarios', ['role' => 'locatario', 'por_pagina' => 100]);
$locatarios    = $resLocatarios['dados'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imovelId   = trim($_POST['imovel_id']   ?? '');
    $locatarioId= trim($_POST['locatario_id']?? '');
    $dataInicio = trim($_POST['data_inicio'] ?? '');
    $dataFim    = trim($_POST['data_fim']    ?? '');
    $valor      = trim($_POST['valor_mensal']?? '');

    if (!$imovelId || !$locatarioId || !$dataInicio || !$dataFim) {
        $erro = 'Imóvel, locatário, data início e data fim são obrigatórios.';
    } else {
        $res = ApiClient::post('/contratos', [
            'imovel_id'    => $imovelId,
            'locatario_id' => $locatarioId,
            'data_inicio'  => $dataInicio,
            'data_fim'     => $dataFim,
            'valor_mensal' => $valor !== '' ? (float)$valor : null,
        ]);

        if (!empty($res['sucesso'])) {
            redirect(base_url('contratos/index.php'));
        } else {
            $erro = $res['erro'] ?? 'Erro ao criar contrato.';
        }
    }
}

$pageTitle  = 'Novo contrato';
$activeMenu = 'contratos';
require ONECHECK_ROOT . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="oc-page-header mb-0">
        <h2>Novo contrato</h2>
        <p>Vincule um imóvel a um locatário</p>
    </div>
    <a href="<?= e(base_url('contratos/index.php')) ?>" class="btn btn-outline-secondary btn-sm">
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
                    <label class="form-label">Imóvel <span style="color:#f87171">*</span></label>
                    <select name="imovel_id" class="form-select" required>
                        <option value="">Selecione um imóvel...</option>
                        <?php foreach ($imoveis as $im): ?>
                        <option value="<?= e($im['id']) ?>"><?= e($im['tipo'] ?? 'Imóvel') ?> — <?= e($im['tamanho'] ?? '') ?></option>
                        <?php endforeach; ?>
                        <?php if (!$imoveis): ?>
                        <option disabled>Nenhum imóvel disponível</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Locatário <span style="color:#f87171">*</span></label>
                    <select name="locatario_id" class="form-select" required>
                        <option value="">Selecione um locatário...</option>
                        <?php foreach ($locatarios as $loc): ?>
                        <option value="<?= e($loc['id']) ?>"><?= e($loc['nome'] ?? '') ?> — <?= e($loc['email'] ?? '') ?></option>
                        <?php endforeach; ?>
                        <?php if (!$locatarios): ?>
                        <option disabled>Nenhum locatário cadastrado</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Data início <span style="color:#f87171">*</span></label>
                    <input type="date" name="data_inicio" class="form-control" required value="<?= e($_POST['data_inicio'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Data fim <span style="color:#f87171">*</span></label>
                    <input type="date" name="data_fim" class="form-control" required value="<?= e($_POST['data_fim'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Valor mensal (R$)</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="number" step="0.01" min="0" name="valor_mensal" class="form-control"
                               placeholder="1500.00" value="<?= e($_POST['valor_mensal'] ?? '') ?>">
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Criar contrato
                </button>
                <a href="<?= e(base_url('contratos/index.php')) ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

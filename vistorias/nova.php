<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/includes/auth_api.php';
require_once dirname(__DIR__) . '/includes/rbac.php';
api_require_page('vistorias');
if (!api_can_create('vistorias')) {
    flash_set('error', 'Sem permissão para criar vistorias.');
    redirect(base_url('vistorias/index.php'));
}

$erro = '';

// 1. Buscar dados iniciais em paralelo (Contratos, Imóveis e Vistoriadores)
$reqMulti = [
    'contratos' => '/contratos?status=ativo&por_pagina=100',
    'imoveis'   => '/imoveis?por_pagina=100',
    'usuarios'  => '/usuarios?role=vistoriador&por_pagina=100'
];
$resMulti = ApiClient::multi_get($reqMulti);

$contratos    = $resMulti['contratos']['dados'] ?? [];
$vistoriadores = $resMulti['usuarios']['dados']  ?? [];

// Mapear imóveis para nome amigável
$imoveisMap = [];
foreach (($resMulti['imoveis']['dados'] ?? []) as $im) {
    $imoveisMap[$im['id']] = ($im['tipo'] ?? 'Imóvel') . ' · ' . ($im['tamanho'] ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contratoId    = trim($_POST['contrato_id']    ?? '');
    $vistoriadorId = trim($_POST['vistoriador_id'] ?? '');
    $tipo          = trim($_POST['tipo']           ?? 'inicial');
    $data          = trim($_POST['data']           ?? date('Y-m-d'));

    if (!$contratoId || !$vistoriadorId) {
        $erro = 'Selecione o contrato e o vistoriador.';
    } else {
        // REGRA DE NEGÓCIO: Verificar se já existe vistoria inicial para este contrato
        $jaExisteInicial = false;
        if ($tipo === 'inicial') {
            $resCks = ApiClient::get('/contratos/' . $contratoId . '/checklists');
            foreach (($resCks['dados'] ?? []) as $ck) {
                if (($ck['tipo'] ?? '') === 'inicial') {
                    $jaExisteInicial = true;
                    break;
                }
            }
        }

        if ($jaExisteInicial) {
            $erro = 'Este contrato já possui uma vistoria de entrada (inicial).';
        } else {
            $res = ApiClient::post('/contratos/' . $contratoId . '/checklists', [
                'tipo'           => $tipo,
                'data_vistoria'  => $data,
                'vistoriador_id' => $vistoriadorId
            ]);

            if (!empty($res['sucesso'])) {
                flash_set('success', 'Vistoria criada com sucesso.');
                redirect(base_url('vistorias/index.php'));
            } else {
                if (!empty($res['erros'])) {
                    $erro = is_array($res['erros']) ? implode(' | ', $res['erros']) : $res['erros'];
                } else {
                    $erro = $res['erro'] ?? 'Erro desconhecido ao criar vistoria.';
                }
            }
        }
    }
}

$pageTitle  = 'Nova vistoria';
$activeMenu = 'vistorias';
require ONECHECK_ROOT . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="oc-page-header mb-0">
        <h2>Nova vistoria</h2>
        <p>Crie um checklist de vistoria para um contrato</p>
    </div>
    <a href="<?= e(base_url('vistorias/index.php')) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Voltar
    </a>
</div>

<?php if ($erro): ?>
    <div class="alert alert-danger mb-3">
        <i class="bi bi-exclamation-octagon me-2"></i><?= e($erro) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <?php if (!$contratos): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Nenhum contrato ativo encontrado. <a href="<?= e(base_url('contratos/novo.php')) ?>">Crie um contrato primeiro →</a>
        </div>
        <?php else: ?>
        <form method="post" autocomplete="off">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Contrato <span class="text-danger">*</span></label>
                    <select name="contrato_id" class="form-select" required>
                        <option value="">Selecione um contrato...</option>
                        <?php foreach ($contratos as $ct):
                            $nomeImovel = $imoveisMap[$ct['imovel_id']] ?? 'Imóvel #' . $ct['imovel_id'];
                        ?>
                        <option value="<?= e($ct['id']) ?>" <?= ($_POST['contrato_id'] ?? '') == $ct['id'] ? 'selected' : '' ?>>
                            <?= e($nomeImovel) ?> · Início: <?= e(substr($ct['data_inicio'] ?? '', 0, 10)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Vistoriador Responsável <span class="text-danger">*</span></label>
                    <select name="vistoriador_id" class="form-select" required>
                        <option value="">Selecione um vistoriador...</option>
                        <?php foreach ($vistoriadores as $v): ?>
                        <option value="<?= e($v['id']) ?>" <?= ($_POST['vistoriador_id'] ?? '') == $v['id'] ? 'selected' : '' ?>>
                            <?= e($v['nome'] ?? 'Sem nome') ?> (<?= e($v['email'] ?? '') ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Tipo de vistoria <span class="text-danger">*</span></label>
                    <select name="tipo" class="form-select" required>
                        <option value="inicial" <?= ($_POST['tipo'] ?? '') === 'inicial' ? 'selected' : '' ?>>Entrada (Inicial)</option>
                        <option value="encerramento" <?= ($_POST['tipo'] ?? '') === 'encerramento' ? 'selected' : '' ?>>Saída (Encerramento)</option>
                    </select>
                    <div class="form-text">A API aceita apenas 'inicial' ou 'encerramento'.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Data da vistoria <span class="text-danger">*</span></label>
                    <input type="date" name="data" class="form-control" required value="<?= $_POST['data'] ?? date('Y-m-d') ?>">
                </div>
            </div>

            <div class="mt-4 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Criar vistoria
                </button>
                <a href="<?= e(base_url('vistorias/index.php')) ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

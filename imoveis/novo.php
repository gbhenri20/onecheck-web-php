<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/includes/auth_api.php';
api_require_login();

$erro  = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo      = trim($_POST['codigo'] ?? '');
    $titulo      = trim($_POST['titulo'] ?? '');
    $tipo        = trim($_POST['tipo']   ?? '');
    $tamNum      = trim($_POST['tamanho_num'] ?? '');
    $garagem     = ($_POST['garagem'] ?? 'nenhuma') === 'sim';
    $vagas       = (int)($_POST['garagem_vagas'] ?? 1);
    $status      = trim($_POST['status'] ?? 'disponivel');
    $observacoes = trim($_POST['observacoes'] ?? '');

    if ($tipo === '' || $tamNum === '' || $titulo === '') {
        $erro = 'Título, tipo e tamanho são obrigatórios.';
    } else {
        $resImovel = ApiClient::post('/imoveis', [
            'codigo'        => $codigo ?: null,
            'titulo'        => $titulo,
            'tipo'          => $tipo,
            'tamanho'       => $tamNum . 'm²',
            'garagem'       => $garagem,
            'garagem_vagas' => $vagas,
            'status'        => $status,
            'observacoes'   => $observacoes,
        ]);

        if (!empty($resImovel['sucesso']) && !empty($resImovel['dados']['id'])) {
            $imovelId = $resImovel['dados']['id'];
            $rua    = trim($_POST['rua']    ?? '');
            $numero = trim($_POST['numero'] ?? '');
            $bairro = trim($_POST['bairro'] ?? '');
            $cidade = trim($_POST['cidade'] ?? '');
            $estado = strtoupper(trim($_POST['estado'] ?? ''));
            $cep    = preg_replace('/\D/', '', $_POST['cep'] ?? '');

            if ($rua !== '' && $cidade !== '' && strlen($estado) === 2) {
                ApiClient::post('/imoveis/' . $imovelId . '/endereco', [
                    'rua'         => $rua,
                    'numero'      => $numero,
                    'complemento' => trim($_POST['complemento'] ?? ''),
                    'bairro'      => $bairro,
                    'cidade'      => $cidade,
                    'estado'      => $estado,
                    'cep'         => $cep,
                ]);
            }
            redirect(base_url('imoveis/index.php'));
        } else {
            $erro = $resImovel['erro'] ?? ($resImovel['erros'] ? implode(', ', $resImovel['erros']) : 'Erro ao cadastrar imóvel.');
        }
    }
}

$pageTitle  = 'Novo imóvel';
$activeMenu = 'imoveis';
require ONECHECK_ROOT . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="oc-page-header mb-0">
        <h2>Novo imóvel</h2>
        <p>Preencha os dados para cadastrar um novo imóvel</p>
    </div>
    <a href="<?= e(base_url('imoveis/index.php')) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Voltar
    </a>
</div>

<?php if ($erro): ?>
<div class="alert alert-danger mb-3"><i class="bi bi-exclamation-triangle me-2"></i><?= e($erro) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" autocomplete="off">
            <?php 
            $modo = 'novo';
            require '_form_campos.php'; 
            ?>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Salvar imóvel
                </button>
                <a href="<?= e(base_url('imoveis/index.php')) ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

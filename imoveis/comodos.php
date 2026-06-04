<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$id = get_int('id');
$pdo = Database::pdo();
$imovel = $pdo->prepare('SELECT id, codigo, titulo FROM imoveis WHERE id = ?');
$imovel->execute([$id]);
$imovel = $imovel->fetch();

if (!$imovel) {
    flash_set('error', 'Imóvel não encontrado.');
    redirect(base_url('imoveis/index.php'));
}

$cfg = ImovelService::config();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'adicionar') {
        $tipo = $_POST['tipo'] ?? '';
        $desc = post_str('descricao');
        if ($tipo === '' || !isset($cfg['comodos'][$tipo])) {
            flash_set('error', 'Selecione um tipo de cômodo válido.');
        } else {
            ImovelService::adicionarComodo($id, $tipo, $desc);
            flash_set('success', 'Cômodo adicionado.');
        }
    }
    if ($acao === 'remover') {
        $cid = $_POST['comodo_id'] ?? '';
        if ($cid !== '') {
            ImovelService::removerComodo($cid, $id);
            flash_set('success', 'Cômodo removido.');
        }
    }
    redirect(base_url('imoveis/comodos.php?id=' . $id));
}

$comodos = ImovelService::listarComodos($id);

$pageTitle = 'Cômodos';
$activeMenu = 'imoveis';
require ONECHECK_ROOT . '/includes/header.php';
flash_render();
page_header('Cômodos — ' . $imovel['codigo'], $imovel['titulo'],
    '<a href="' . e(base_url('imoveis/detalhes.php?id=' . $id)) . '" class="btn btn-link btn-sm">Voltar</a>');
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Adicionar cômodo (RF05)</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="acao" value="adicionar">
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select" required>
                            <?php foreach ($cfg['comodos'] as $val => $label): ?>
                            <option value="<?= e($val) ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição (opcional)</label>
                        <input name="descricao" class="form-control" placeholder="Ex: Quarto 2, Suíte master">
                    </div>
                    <button class="btn btn-primary w-100">Adicionar</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Cômodos cadastrados (<?= count($comodos) ?>)</div>
            <ul class="list-group list-group-flush">
                <?php if (!$comodos): ?>
                <li class="list-group-item text-muted">Nenhum cômodo. Ao criar o imóvel, sala/cozinha/quarto/banheiro são adicionados automaticamente.</li>
                <?php else: foreach ($comodos as $c): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?= e($cfg['comodos'][$c['tipo']] ?? $c['tipo']) ?></strong>
                        <?php if ($c['descricao']): ?>
                        <span class="text-muted"> — <?= e($c['descricao']) ?></span>
                        <?php endif; ?>
                    </div>
                    <form method="post" class="d-inline" onsubmit="return confirm('Remover este cômodo?');">
                        <input type="hidden" name="acao" value="remover">
                        <input type="hidden" name="comodo_id" value="<?= e($c['id']) ?>">
                        <button class="btn btn-sm btn-outline-danger">Remover</button>
                    </form>
                </li>
                <?php endforeach; endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

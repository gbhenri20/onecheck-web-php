<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$user = Auth::user();
$pdo = Database::pdo();
$imoveis = $pdo->query('SELECT id, codigo, titulo FROM imoveis ORDER BY codigo')->fetchAll();

$preImovel = get_int('imovel_id');
$preVistoria = get_int('vistoria_id');
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imovelId = (int) ($_POST['imovel_id'] ?? 0);
    $vistoriaId = (int) ($_POST['vistoria_id'] ?? 0) ?: null;
    $titulo = post_str('titulo');
    $desc = post_str('descricao');
    $prioridade = $_POST['prioridade'] ?? 'media';

    if ($imovelId < 1 || $titulo === '') {
        $erro = 'Imóvel e título são obrigatórios.';
    } else {
        $pdo->prepare(
            'INSERT INTO problemas (imovel_id, vistoria_id, titulo, descricao, prioridade, criado_por)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$imovelId, $vistoriaId, $titulo, $desc ?: null, $prioridade, $user['id']]);
        flash_set('success', 'Problema registrado.');
        redirect(base_url('problemas/index.php'));
    }
}

$pageTitle = 'Novo problema';
$activeMenu = 'problemas';
require ONECHECK_ROOT . '/includes/header.php';
page_header('Registrar problema', '', '<a href="' . e(base_url('problemas/index.php')) . '" class="btn btn-link btn-sm">Voltar</a>');
?>

<?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Imóvel</label>
                <select name="imovel_id" class="form-select" required>
                    <?php foreach ($imoveis as $i): ?>
                    <option value="<?= (int) $i['id'] ?>" <?= $preImovel === (int) $i['id'] ? 'selected' : '' ?>>
                        <?= e($i['codigo']) ?> — <?= e($i['titulo']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Vistoria (opcional)</label>
                <input type="number" name="vistoria_id" class="form-control" value="<?= $preVistoria ?: '' ?>" placeholder="ID">
            </div>
            <div class="col-12">
                <label class="form-label">Título</label>
                <input name="titulo" class="form-control" required placeholder="Ex: Infiltração no banheiro">
            </div>
            <div class="col-md-4">
                <label class="form-label">Prioridade</label>
                <select name="prioridade" class="form-select">
                    <?php foreach (['baixa','media','alta','urgente'] as $pr): ?>
                    <option value="<?= e($pr) ?>"><?= e(ucfirst($pr)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Descrição</label>
                <textarea name="descricao" class="form-control" rows="4"></textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

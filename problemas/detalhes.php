<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$id = get_int('id');
$pdo = Database::pdo();

$stmt = $pdo->prepare(
    'SELECT p.*, i.codigo, i.titulo, u.nome AS autor
     FROM problemas p
     INNER JOIN imoveis i ON i.id = p.imovel_id
     INNER JOIN usuarios u ON u.id = p.criado_por
     WHERE p.id = ?'
);
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    flash_set('error', 'Problema não encontrado.');
    redirect(base_url('problemas/index.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';
    $validos = ['aberto', 'em_analise', 'resolvido', 'cancelado'];
    if (in_array($status, $validos, true)) {
        $resolvido = $status === 'resolvido' ? date('Y-m-d H:i:s') : null;
        $pdo->prepare('UPDATE problemas SET status = ?, resolvido_em = ? WHERE id = ?')
            ->execute([$status, $resolvido, $id]);
        flash_set('success', 'Status atualizado.');
        redirect(base_url('problemas/detalhes.php?id=' . $id));
    }
}

$pageTitle = $p['titulo'];
$activeMenu = 'problemas';
require ONECHECK_ROOT . '/includes/header.php';
flash_render();
page_header($p['titulo'], $p['codigo'] . ' — ' . $p['titulo'],
    '<a href="' . e(base_url('problemas/index.php')) . '" class="btn btn-link btn-sm">Voltar</a>');
?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <p class="mb-2"><?= badge_status('prioridade', $p['prioridade']) ?> <?= badge_status('problema', $p['status']) ?></p>
                <?php if ($p['descricao']): ?>
                <p><?= nl2br(e($p['descricao'])) ?></p>
                <?php else: ?>
                <p class="text-muted">Sem descrição.</p>
                <?php endif; ?>
                <hr>
                <dl class="row small mb-0">
                    <dt class="col-sm-3">Imóvel</dt>
                    <dd class="col-sm-9">
                        <a href="<?= e(base_url('imoveis/detalhes.php?id=' . $p['imovel_id'])) ?>"><?= e($p['codigo']) ?></a>
                    </dd>
                    <?php if ($p['vistoria_id']): ?>
                    <dt class="col-sm-3">Vistoria</dt>
                    <dd class="col-sm-9">
                        <a href="<?= e(base_url('vistorias/detalhes.php?id=' . $p['vistoria_id'])) ?>">#<?= (int) $p['vistoria_id'] ?></a>
                    </dd>
                    <?php endif; ?>
                    <dt class="col-sm-3">Autor</dt><dd class="col-sm-9"><?= e($p['autor']) ?></dd>
                    <dt class="col-sm-3">Criado em</dt><dd class="col-sm-9"><?= format_datetime($p['criado_em']) ?></dd>
                    <dt class="col-sm-3">Resolvido em</dt><dd class="col-sm-9"><?= format_datetime($p['resolvido_em']) ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Atualizar status</div>
            <div class="card-body">
                <form method="post">
                    <select name="status" class="form-select mb-2">
                        <?php foreach (['aberto','em_analise','resolvido','cancelado'] as $s): ?>
                        <option value="<?= e($s) ?>" <?= $p['status'] === $s ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary w-100">Salvar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$pdo = Database::pdo();

$vistoriaId = isset($_GET['vistoria_id']) ? (int) $_GET['vistoria_id'] : null;
$comodo = trim($_GET['comodo'] ?? '');

$sql = 'SELECT f.*, v.id AS vid, i.codigo AS imovel_codigo, i.titulo AS imovel_titulo
        FROM vistoria_fotos f
        INNER JOIN vistorias v ON v.id = f.vistoria_id
        INNER JOIN imoveis i ON i.id = v.imovel_id
        WHERE 1=1';
$params = [];

if ($vistoriaId) {
    $sql .= ' AND f.vistoria_id = ?';
    $params[] = $vistoriaId;
}
if ($comodo !== '') {
    $sql .= ' AND f.comodo LIKE ?';
    $params[] = '%' . $comodo . '%';
}

$sql .= ' ORDER BY f.criado_em DESC LIMIT 200';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$fotos = $stmt->fetchAll();

$vistorias = $pdo->query(
    'SELECT v.id, i.codigo, v.data_vistoria
     FROM vistorias v
     INNER JOIN imoveis i ON i.id = v.imovel_id
     ORDER BY v.id DESC LIMIT 50'
)->fetchAll();

$pageTitle = 'Arquivo de fotos';
$activeMenu = 'vistorias';
require ONECHECK_ROOT . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Fotos das vistorias</h1>
        <p class="text-muted mb-0">Imagens enviadas pelo app mobile e pelo painel web</p>
    </div>
    <a href="<?= e(base_url('vistorias/index.php')) ?>" class="btn btn-outline-secondary btn-sm">Vistorias</a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-md-4">
                <label class="form-label small">Vistoria</label>
                <select name="vistoria_id" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <?php foreach ($vistorias as $v): ?>
                    <option value="<?= (int) $v['id'] ?>" <?= $vistoriaId === (int) $v['id'] ? 'selected' : '' ?>>
                        #<?= (int) $v['id'] ?> — <?= e($v['codigo']) ?> (<?= e($v['data_vistoria']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Cômodo</label>
                <input type="text" name="comodo" class="form-control form-control-sm"
                       placeholder="sala, cozinha..." value="<?= e($comodo) ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                <a href="<?= e(base_url('vistorias/fotos.php')) ?>" class="btn btn-link btn-sm">Limpar</a>
            </div>
        </form>
    </div>
</div>

<?php if (!$fotos): ?>
<div class="alert alert-info">Nenhuma foto encontrada.</div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($fotos as $f): ?>
    <div class="col-6 col-md-4 col-lg-3">
        <div class="card photo-card h-100 shadow-sm">
            <a href="<?= e(base_url($f['arquivo_path'])) ?>" target="_blank" rel="noopener">
                <img src="<?= e(base_url($f['arquivo_path'])) ?>" alt="<?= e($f['comodo']) ?>">
            </a>
            <div class="card-body p-2">
                <div class="fw-semibold small"><?= e($f['comodo']) ?></div>
                <div class="text-muted" style="font-size: .75rem;">
                    <?= e($f['imovel_codigo']) ?> · <?= e($f['origem']) ?><br>
                    <?= e(date('d/m/Y H:i', strtotime($f['criado_em']))) ?>
                </div>
                <?php if ($f['observacao']): ?>
                <p class="small mb-0 mt-1"><?= e($f['observacao']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

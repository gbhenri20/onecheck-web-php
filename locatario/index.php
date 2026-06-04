<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireRole('locatario');

$user = Auth::user();
$pdo = Database::pdo();

$contrato = $pdo->prepare(
    'SELECT c.*, i.codigo, i.titulo, i.endereco
     FROM contratos c
     INNER JOIN imoveis i ON i.id = c.imovel_id
     WHERE c.locatario_usuario_id = ? AND c.status = \'ativo\'
     ORDER BY c.id DESC LIMIT 1'
);
$contrato->execute([$user['id']]);
$meuContrato = $contrato->fetch();

$pageTitle = 'Área do locatário';
require ONECHECK_ROOT . '/locatario/_header.php';
?>

<div class="mb-4">
    <h1 class="h3">Olá, <?= e($user['nome']) ?></h1>
    <p class="text-muted">Portal do locatário — Sprint 5 trará checklist e problemas completos (RF21–RF25).</p>
</div>

<?php if ($meuContrato): ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6 fw-semibold">Seu imóvel</h2>
        <p class="mb-1"><strong><?= e($meuContrato['codigo']) ?></strong> — <?= e($meuContrato['titulo']) ?></p>
        <p class="text-muted small mb-2"><?= e($meuContrato['endereco']) ?></p>
        <p class="small mb-0">Contrato <?= e($meuContrato['numero']) ?> · desde <?= format_date($meuContrato['data_inicio']) ?></p>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info">Nenhum contrato ativo vinculado ao seu usuário. O admin deve associar seu login ao contrato.</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h3 class="h6">Checklist</h3>
                <p class="small text-muted">Em breve: visualizar e aceitar/rejeitar vistoria (RF21–RF24).</p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h3 class="h6">Problemas</h3>
                <p class="small text-muted">Em breve: registrar problema com foto (RF25).</p>
            </div>
        </div>
    </div>
</div>

<?php require ONECHECK_ROOT . '/locatario/_footer.php'; ?>

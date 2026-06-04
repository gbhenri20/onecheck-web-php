<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$id = get_int('id');
$pdo = Database::pdo();

$stmt = $pdo->prepare(
    'SELECT v.id, v.status, i.codigo, i.titulo FROM vistorias v
     INNER JOIN imoveis i ON i.id = v.imovel_id WHERE v.id = ?'
);
$stmt->execute([$id]);
$vistoria = $stmt->fetch();

if (!$vistoria) {
    flash_set('error', 'Vistoria não encontrada.');
    redirect(base_url('vistorias/index.php'));
}

$comodos = [
    'sala' => 'Sala',
    'cozinha' => 'Cozinha',
    'quarto_1' => 'Quarto 1',
    'quarto_2' => 'Quarto 2',
    'banheiro' => 'Banheiro',
    'area_servico' => 'Área de serviço',
    'varanda' => 'Varanda',
    'garagem' => 'Garagem',
];

$tabelaExiste = false;
try {
    $pdo->query('SELECT 1 FROM vistoria_checklist LIMIT 1');
    $tabelaExiste = true;
} catch (PDOException) {
    $tabelaExiste = false;
}

if ($tabelaExiste && $_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($comodos as $slug => $label) {
        $sit = $_POST['situacao'][$slug] ?? 'ok';
        $obs = trim($_POST['observacao'][$slug] ?? '');
        if (!in_array($sit, ['ok', 'atencao', 'problema'], true)) {
            $sit = 'ok';
        }
        $pdo->prepare(
            'INSERT INTO vistoria_checklist (vistoria_id, comodo, situacao, observacao)
             VALUES (?, ?, ?, ?)
             ON CONFLICT (vistoria_id, comodo) DO UPDATE SET
               situacao = EXCLUDED.situacao,
               observacao = EXCLUDED.observacao'
        )->execute([$id, $slug, $sit, $obs !== '' ? $obs : null]);
    }
    flash_set('success', 'Checklist salvo.');
    redirect(base_url('vistorias/checklist.php?id=' . $id));
}

$itens = [];
if ($tabelaExiste) {
    $rows = $pdo->prepare('SELECT comodo, situacao, observacao FROM vistoria_checklist WHERE vistoria_id = ?');
    $rows->execute([$id]);
    foreach ($rows->fetchAll() as $r) {
        $itens[$r['comodo']] = $r;
    }
}

$pageTitle = 'Checklist';
$activeMenu = 'vistorias';
require ONECHECK_ROOT . '/includes/header.php';
flash_render();
page_header('Checklist — Vistoria #' . $id, $vistoria['codigo'] . ' — ' . $vistoria['titulo'],
    '<a href="' . e(base_url('vistorias/detalhes.php?id=' . $id)) . '" class="btn btn-link btn-sm">Voltar</a>');
?>

<?php if (!$tabelaExiste): ?>
<div class="alert alert-warning">
    Execute o SQL <code>database/migrations/001_schema_postgres.sql</code> (ou migração MySQL equivalente) para habilitar o checklist.
</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Cômodo</th>
                            <th>Situação</th>
                            <th>Observação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comodos as $slug => $label):
                            $item = $itens[$slug] ?? ['situacao' => 'ok', 'observacao' => ''];
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= e($label) ?></td>
                            <td>
                                <select name="situacao[<?= e($slug) ?>]" class="form-select form-select-sm">
                                    <option value="ok" <?= ($item['situacao'] ?? '') === 'ok' ? 'selected' : '' ?>>OK</option>
                                    <option value="atencao" <?= ($item['situacao'] ?? '') === 'atencao' ? 'selected' : '' ?>>Atenção</option>
                                    <option value="problema" <?= ($item['situacao'] ?? '') === 'problema' ? 'selected' : '' ?>>Problema</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="observacao[<?= e($slug) ?>]" class="form-control form-control-sm"
                                       value="<?= e($item['observacao'] ?? '') ?>" placeholder="Opcional">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button class="btn btn-primary">Salvar checklist</button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

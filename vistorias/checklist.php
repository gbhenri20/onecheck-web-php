<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/includes/auth_api.php';
require_once dirname(__DIR__) . '/includes/rbac.php';
api_require_page('vistorias');

$id = get_str('id');
$contratoId = get_str('contrato_id');

if ($id === '') {
    flash_set('error', 'Checklist não informado.');
    redirect(base_url('vistorias/index.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = post_str('acao');
    if ($acao === 'aceitar') {
        $resAc = ApiClient::patch('/checklists/' . urlencode($id) . '/aceitar', []);
        flash_set(!empty($resAc['sucesso']) ? 'success' : 'error', $resAc['sucesso'] ? 'Vistoria aceita com sucesso.' : ($resAc['erro'] ?? 'Erro ao aceitar.'));
    } elseif ($acao === 'rejeitar') {
        $resAc = ApiClient::patch('/checklists/' . urlencode($id) . '/rejeitar', []);
        flash_set(!empty($resAc['sucesso']) ? 'success' : 'error', $resAc['sucesso'] ? 'Vistoria rejeitada.' : ($resAc['erro'] ?? 'Erro ao rejeitar.'));
    }
    redirect(base_url('vistorias/checklist.php?id=' . urlencode($id) . '&contrato_id=' . urlencode($contratoId)));
}

$res = ApiClient::get('/checklists/' . urlencode($id));
if (empty($res['sucesso']) || empty($res['dados'])) {
    flash_set('error', $res['erro'] ?? 'Checklist não encontrado na API.');
    redirect(base_url('vistorias/index.php'));
}

$checklist = $res['dados'];
$contratoId = $contratoId ?: ($checklist['contrato_id'] ?? '');

$itensCatalog = [];
$resCat = ApiClient::get('/itens-vistoria');
foreach (($resCat['dados'] ?? []) as $cat) {
    $itensCatalog[$cat['id']] = $cat['nome'] ?? 'Item';
}

$comodosMap = [];
foreach (($checklist['comodos'] ?? []) as $c) {
    $comodosMap[$c['id']] = $c['nome'] ?? $c['tipo'] ?? $c['descricao'] ?? 'Cômodo';
}

$rooms = [];
foreach (($checklist['itens'] ?? []) as $item) {
    $comodoId = $item['comodo_id'] ?? 'sem-comodo';
    if (!isset($rooms[$comodoId])) {
        $rooms[$comodoId] = [
            'nome' => $comodosMap[$comodoId] ?? 'Cômodo',
            'itens' => [],
        ];
    }
    $itemVistoriaId = $item['item_vistoria_id'] ?? '';
    $rooms[$comodoId]['itens'][] = [
        'label' => $itensCatalog[$itemVistoriaId] ?? 'Item de vistoria',
        'estado' => $item['estado'] ?? null,
        'observacao' => $item['observacao'] ?? '',
        'fotos' => $item['fotos'] ?? [],
    ];
}

$statusLabels = [
    'em_preenchimento' => ['Em preenchimento', 'bg-warning'],
    'pendente_aceite'  => ['Pendente aceite', 'bg-info'],
    'aceito'           => ['Aceito', 'bg-success'],
    'rejeitado'        => ['Rejeitado', 'bg-danger'],
    'pendente_revisao' => ['Pendente revisão', 'bg-warning'],
];
$st = $checklist['status'] ?? '';
[$statusLabel, $statusClass] = $statusLabels[$st] ?? [$st, 'bg-secondary'];

$tipoBadge = ($checklist['tipo'] ?? '') === 'encerramento' ? 'badge-final' : 'badge-inicial';

function foto_full_url(string $url): string {
    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
        return $url;
    }
    $base = rtrim(API_UPLOAD_URL, '/');
    return $base . (str_starts_with($url, '/') ? $url : '/' . $url);
}

$pageTitle = 'Checklist';
$activeMenu = 'vistorias';
require ONECHECK_ROOT . '/includes/header.php';
flash_render();

page_header(
    'Checklist — ' . ucfirst($checklist['tipo'] ?? 'vistoria'),
    'Contrato #' . e(substr($contratoId, 0, 8)) . ' · Data: ' . e(substr($checklist['data_vistoria'] ?? '—', 0, 10)),
    '<a href="' . e(base_url('vistorias/index.php')) . '" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Voltar</a>'
);
?>

<div class="d-flex flex-wrap gap-2 mb-4">
    <span class="badge <?= e($tipoBadge) ?>"><?= e(ucfirst($checklist['tipo'] ?? '')) ?></span>
    <span class="badge <?= e($statusClass) ?>"><?= e($statusLabel) ?></span>
    <span class="badge bg-secondary">ID: <?= e(substr($id, 0, 8)) ?>…</span>
</div>

<?php if (($checklist['status'] ?? '') === 'pendente_aceite' && in_array(api_role(), ['admin', 'gestor', 'locatario'], true)): ?>
<div class="card mb-4 border-info">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <strong><i class="bi bi-hand-index me-1"></i>Vistoria aguardando seu aceite</strong>
            <p class="mb-0 small text-muted">Revise os itens abaixo e confirme ou rejeite a vistoria enviada pelo vistoriador.</p>
        </div>
        <div class="d-flex gap-2">
            <form method="post" class="d-inline">
                <input type="hidden" name="acao" value="aceitar">
                <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check-lg me-1"></i>Aceitar vistoria</button>
            </form>
            <form method="post" class="d-inline" onsubmit="return confirm('Rejeitar esta vistoria?');">
                <input type="hidden" name="acao" value="rejeitar">
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-x-lg me-1"></i>Rejeitar</button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!$rooms): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-clipboard-check" style="font-size:48px;color:var(--oc-border)"></i>
        <p class="mt-3 mb-1" style="color:var(--oc-muted)">Nenhum item preenchido ainda.</p>
        <p style="font-size:12px;color:var(--oc-hint)">O vistoriador preenche este checklist pelo app mobile.</p>
    </div>
</div>
<?php else: ?>
<?php foreach ($rooms as $comodoId => $room): ?>
<div class="oc-checklist-room">
    <div class="oc-checklist-room-header">
        <i class="bi bi-door-open me-2" style="color:var(--oc-primary)"></i><?= e($room['nome']) ?>
    </div>
    <?php foreach ($room['itens'] as $item): ?>
    <div class="oc-item-card">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div class="oc-item-label"><?= e($item['label']) ?></div>
            <?php if ($item['estado']): ?>
            <span class="oc-estado oc-estado-<?= e($item['estado']) ?>"><?= e($item['estado']) ?></span>
            <?php else: ?>
            <span class="badge bg-secondary">Não preenchido</span>
            <?php endif; ?>
        </div>
        <?php if ($item['observacao'] !== ''): ?>
        <div class="oc-item-meta mt-2">
            <i class="bi bi-chat-left-text me-1"></i><?= e($item['observacao']) ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($item['fotos'])): ?>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <?php foreach ($item['fotos'] as $foto): ?>
            <a href="<?= e(foto_full_url($foto['url'] ?? '')) ?>" target="_blank" rel="noopener">
                <img src="<?= e(foto_full_url($foto['url'] ?? '')) ?>" alt="Foto" class="oc-photo-thumb">
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

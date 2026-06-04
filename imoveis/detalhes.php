<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$id = get_int('id');
if ($id < 1) {
    flash_set('error', 'Imóvel inválido.');
    redirect(base_url('imoveis/index.php'));
}

$pdo = Database::pdo();
$stmt = $pdo->prepare('SELECT * FROM imoveis WHERE id = ?');
$stmt->execute([$id]);
$imovel = $stmt->fetch();

if (!$imovel) {
    flash_set('error', 'Imóvel não encontrado.');
    redirect(base_url('imoveis/index.php'));
}

$end = ImovelService::getEnderecoPrincipal($id);
$cfg = ImovelService::config();
$comodos = ImovelService::listarComodos($id);

$vistorias = $pdo->prepare(
    'SELECT v.*, u.nome AS vistoriador,
            (SELECT COUNT(*) FROM vistoria_fotos f WHERE f.vistoria_id = v.id) AS fotos
     FROM vistorias v
     INNER JOIN usuarios u ON u.id = v.usuario_id
     WHERE v.imovel_id = ? ORDER BY v.id DESC LIMIT 10'
);
$vistorias->execute([$id]);

$contratos = $pdo->prepare('SELECT * FROM contratos WHERE imovel_id = ? ORDER BY id DESC LIMIT 5');
$contratos->execute([$id]);

$problemas = $pdo->prepare(
    "SELECT * FROM problemas WHERE imovel_id = ? AND status IN ('aberto','em_analise') ORDER BY id DESC LIMIT 5"
);
$problemas->execute([$id]);

$subtitulo = $end
    ? ImovelService::enderecoFormatado($end) . ', ' . $end['cidade'] . '/' . $end['estado']
    : $imovel['endereco'] . ', ' . $imovel['cidade'] . '/' . $imovel['estado'];

$pageTitle = 'Imóvel ' . $imovel['codigo'];
$activeMenu = 'imoveis';
require ONECHECK_ROOT . '/includes/header.php';
flash_render();
page_header($imovel['codigo'] . ' — ' . $imovel['titulo'], $subtitulo,
    '<a href="' . e(base_url('imoveis/mapa.php')) . '" class="btn btn-outline-secondary btn-sm">Mapa</a>'
    . '<a href="' . e(base_url('imoveis/comodos.php?id=' . $id)) . '" class="btn btn-outline-secondary btn-sm">Cômodos</a>'
    . '<a href="' . e(base_url('imoveis/editar.php?id=' . $id)) . '" class="btn btn-outline-secondary btn-sm">Editar</a>'
    . '<a href="' . e(base_url('vistorias/nova.php?imovel_id=' . $id)) . '" class="btn btn-primary btn-sm">Nova vistoria</a>'
    . '<a href="' . e(base_url('imoveis/index.php')) . '" class="btn btn-link btn-sm">Voltar</a>');
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Dados do imóvel</div>
            <div class="card-body small">
                <dl class="row mb-0">
                    <dt class="col-5">Status</dt><dd class="col-7"><?= badge_status('imovel', $imovel['status']) ?></dd>
                    <dt class="col-5">Tipo</dt><dd class="col-7"><?= e($cfg['tipos'][$imovel['tipo']] ?? $imovel['tipo']) ?></dd>
                    <dt class="col-5">Tamanho</dt>
                    <dd class="col-7"><?= $imovel['tamanho_m2'] ? e((string) $imovel['tamanho_m2']) . ' m²' : '—' ?></dd>
                    <dt class="col-5">Garagem</dt>
                    <dd class="col-7"><?= e($cfg['garagem'][$imovel['garagem'] ?? 'nenhuma'] ?? '—') ?></dd>
                    <dt class="col-5">CEP</dt><dd class="col-7"><?= e($end['cep'] ?? $imovel['cep'] ?: '—') ?></dd>
                    <dt class="col-5">Cômodos</dt><dd class="col-7"><?= count($comodos) ?></dd>
                </dl>
                <?php if ($imovel['observacoes']): ?>
                <hr><p class="mb-0"><?= nl2br(e($imovel['observacoes'])) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($end && $end['latitude'] && $end['longitude']): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Localização</div>
            <div class="card-body p-0">
                <div id="mapa-detalhe" style="height: 220px;"></div>
            </div>
            <div class="card-footer small text-muted">
                <?= e($end['latitude']) ?>, <?= e($end['longitude']) ?>
            </div>
        </div>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
        (function() {
          const lat = <?= (float) $end['latitude'] ?>, lng = <?= (float) $end['longitude'] ?>;
          const map = L.map('mapa-detalhe').setView([lat, lng], 16);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
          L.marker([lat, lng]).addTo(map);
        })();
        </script>
        <?php else: ?>
        <div class="alert alert-warning small">Sem coordenadas GPS. Edite o imóvel e marque geocodificação.</div>
        <?php endif; ?>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between">
                <span class="fw-semibold">Cômodos</span>
                <a href="<?= e(base_url('imoveis/comodos.php?id=' . $id)) ?>" class="small">Gerenciar</a>
            </div>
            <div class="card-body py-2">
                <?php if (!$comodos): ?>
                <span class="text-muted small">Nenhum cômodo cadastrado.</span>
                <?php else: ?>
                <div class="d-flex flex-wrap gap-1">
                    <?php foreach ($comodos as $c): ?>
                    <span class="badge text-bg-light text-dark border">
                        <?= e($cfg['comodos'][$c['tipo']] ?? $c['tipo']) ?>
                        <?= $c['descricao'] ? '· ' . e($c['descricao']) : '' ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between">
                <span class="fw-semibold">Últimas vistorias</span>
                <a href="<?= e(base_url('vistorias/index.php?imovel_id=' . $id)) ?>" class="small">Ver todas</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>#</th><th>Tipo</th><th>Data</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php $rows = $vistorias->fetchAll(); if (!$rows): ?>
                    <tr><td colspan="5" class="text-muted text-center py-3">Sem vistorias.</td></tr>
                    <?php else: foreach ($rows as $v): ?>
                    <tr>
                        <td><?= (int) $v['id'] ?></td>
                        <td><?= e($v['tipo']) ?></td>
                        <td><?= format_date($v['data_vistoria']) ?></td>
                        <td><?= badge_status('vistoria', $v['status']) ?></td>
                        <td><a href="<?= e(base_url('vistorias/detalhes.php?id=' . $v['id'])) ?>">Abrir</a></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Contratos</div>
                    <ul class="list-group list-group-flush">
                        <?php $cRows = $contratos->fetchAll(); if (!$cRows): ?>
                        <li class="list-group-item text-muted small">Nenhum contrato.</li>
                        <?php else: foreach ($cRows as $c): ?>
                        <li class="list-group-item d-flex justify-content-between small">
                            <span><?= e($c['numero']) ?></span>
                            <?= badge_status('contrato', $c['status']) ?>
                        </li>
                        <?php endforeach; endif; ?>
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Problemas abertos</div>
                    <ul class="list-group list-group-flush">
                        <?php $pRows = $problemas->fetchAll(); if (!$pRows): ?>
                        <li class="list-group-item text-muted small">Nenhum problema.</li>
                        <?php else: foreach ($pRows as $p): ?>
                        <li class="list-group-item small">
                            <a href="<?= e(base_url('problemas/detalhes.php?id=' . $p['id'])) ?>"><?= e($p['titulo']) ?></a>
                        </li>
                        <?php endforeach; endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

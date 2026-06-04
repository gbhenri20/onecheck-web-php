<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$pontos = ImovelService::listarParaMapa();
$geojson = array_map(static fn($p) => [
    'id'       => (int) $p['id'],
    'codigo'   => $p['codigo'],
    'titulo'   => $p['titulo'],
    'status'   => $p['status'],
    'lat'      => (float) $p['latitude'],
    'lng'      => (float) $p['longitude'],
    'endereco' => trim($p['logradouro'] . ($p['numero'] ? ', ' . $p['numero'] : '') . ' — ' . $p['cidade']),
    'url'      => base_url('imoveis/detalhes.php?id=' . $p['id']),
], $pontos);

$pageTitle  = 'Mapa de imóveis';
$activeMenu = 'mapa';
require ONECHECK_ROOT . '/includes/header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="oc-page-header mb-0">
        <h2>Mapa de imóveis</h2>
        <p><?= count($pontos) ?> imóvel(is) com coordenadas GPS</p>
    </div>
    <a href="<?= e(base_url('imoveis/index.php')) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-list me-1"></i>Lista
    </a>
</div>

<div class="card">
    <div class="card-body p-0" style="border-radius:12px;overflow:hidden">
        <div id="mapa-imoveis" style="height:520px;width:100%"></div>
    </div>
</div>

<?php if (!$pontos): ?>
<div class="alert alert-info mt-3">
    <i class="bi bi-info-circle me-2"></i>
    Nenhum imóvel com coordenadas GPS cadastradas. Edite um imóvel e marque "Atualizar coordenadas GPS".
</div>
<?php endif; ?>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const pontos = <?= json_encode($geojson, JSON_UNESCAPED_UNICODE) ?>;

const map = L.map('mapa-imoveis', { zoomControl: true })
    .setView([-23.55, -46.63], 11);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
}).addTo(map);

const iconVerde = L.divIcon({
    className: '',
    html: '<div style="width:14px;height:14px;background:#22c55e;border:2px solid #fff;border-radius:50%;box-shadow:0 0 6px rgba(34,197,94,.6)"></div>',
    iconSize: [14, 14], iconAnchor: [7, 7], popupAnchor: [0, -10]
});

const markers = [];
pontos.forEach(p => {
    if (!p.lat || !p.lng) return;
    const m = L.marker([p.lat, p.lng], { icon: iconVerde }).addTo(map);
    m.bindPopup(
        `<div style="min-width:180px">
            <strong style="color:#1a1a1a">${p.codigo} · ${p.titulo}</strong><br>
            <small style="color:#666">${p.endereco}</small><br>
            <a href="${p.url}" style="color:#4f8ef7;font-size:12px">Ver detalhes →</a>
        </div>`
    );
    markers.push(m);
});

if (markers.length) {
    const group = L.featureGroup(markers);
    map.fitBounds(group.getBounds().pad(0.25));
}
</script>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

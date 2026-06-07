<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/includes/auth_api.php';
require_once dirname(__DIR__) . '/includes/rbac.php';
api_require_page('mapa');

flash_render();

$res = ApiClient::get('/imoveis', ['por_pagina' => 100, 'com_endereco' => 1]);
$imoveis = $res['dados'] ?? [];

$pontos = [];
$semCoords = 0;
$semEndereco = 0;

foreach ($imoveis as $im) {
    $end = $im['endereco'] ?? null;

    if (!$end || !is_array($end)) {
        $endRes = ApiClient::get('/imoveis/' . urlencode($im['id']) . '/endereco');
        if (empty($endRes['sucesso'])) {
            $semEndereco++;
            continue;
        }
        $end = $endRes['dados'];
    }

    $coords = endereco_coords($end);
    if ($coords) {
        $lat = $coords['latitude'];
        $lng = $coords['longitude'];
        $fonte = 'cadastro';
    } else {
        $geo = Geocoder::geocodeEndereco(
            $end['rua'] ?? $end['logradouro'] ?? '',
            (string) ($end['numero'] ?? ''),
            (string) ($end['bairro'] ?? ''),
            (string) ($end['cidade'] ?? ''),
            (string) ($end['estado'] ?? ''),
            (string) ($end['cep'] ?? '')
        );
        if (!$geo) {
            $semCoords++;
            continue;
        }
        usleep(150000);
        $lat = $geo['latitude'];
        $lng = $geo['longitude'];
        $fonte = 'geocode';
    }

    $pontos[] = [
        'id'       => $im['id'],
        'codigo'   => $im['codigo'] ?? substr($im['id'], 0, 8),
        'titulo'   => $im['titulo'] ?? ($im['tipo'] ?? 'Imóvel'),
        'status'   => $im['status'] ?? '',
        'lat'      => $lat,
        'lng'      => $lng,
        'fonte'    => $fonte,
        'endereco' => trim(($end['rua'] ?? '') . ', ' . ($end['numero'] ?? '') . ' — ' . ($end['cidade'] ?? '')),
        'url'      => base_url('imoveis/editar.php?id=' . urlencode($im['id'])),
    ];
}

$pageTitle  = 'Mapa de imóveis';
$activeMenu = 'mapa';
require ONECHECK_ROOT . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="oc-page-header mb-0">
        <h2>Mapa de imóveis</h2>
        <p><?= count($pontos) ?> imóvel(is) no mapa
            <?php if ($semCoords): ?> · <?= $semCoords ?> sem coordenadas<?php endif; ?>
            <?php if ($semEndereco): ?> · <?= $semEndereco ?> sem endereço<?php endif; ?>
        </p>
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
    Nenhum ponto no mapa. Edite os imóveis e preencha <strong>latitude</strong> e <strong>longitude</strong> no formulário (seção “Localização no mapa”).
</div>
<?php elseif ($semCoords > 0): ?>
<div class="alert alert-warning mt-3 mb-0">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <?= $semCoords ?> imóvel(is) sem coordenadas cadastradas — a geocodificação automática falhou ou a API ainda não foi atualizada.
</div>
<?php endif; ?>

<script>
const pontos = <?= json_encode($pontos, JSON_UNESCAPED_UNICODE) ?>;
const map = L.map('mapa-imoveis').setView([-23.55, -46.63], 11);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap'
}).addTo(map);

const markers = [];
pontos.forEach(p => {
    const m = L.marker([p.lat, p.lng]).addTo(map);
    const origem = p.fonte === 'cadastro' ? 'Coordenadas cadastradas' : 'Geocodificado';
    m.bindPopup(`<strong>${p.codigo} · ${p.titulo}</strong><br><small>${p.endereco}</small><br><small class="text-muted">${origem}</small><br><a href="${p.url}">Ver imóvel →</a>`);
    markers.push(m);
});
if (markers.length) {
    map.fitBounds(L.featureGroup(markers).getBounds().pad(0.25));
}
setTimeout(() => map.invalidateSize(), 300);
</script>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

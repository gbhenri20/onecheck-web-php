<?php
/**
 * Partial: Formulário de Imóveis
 * 
 * Este arquivo é compartilhado entre novo.php e editar.php para garantir consistência.
 * 
 * @var array|null $imovel  Dados do imóvel (se for edição)
 * @var array|null $end     Dados do endereço
 * @var string     $modo    'novo' ou 'editar'
 */
$imovel = $imovel ?? [];
$end    = $end    ?? [];
$modo   = $modo   ?? 'novo';

/**
 * Helper para pegar valores com a seguinte prioridade:
 * 1. Valor enviado via POST (se houver erro e o form recarregar)
 * 2. Valor vindo do Banco de Dados ($data)
 * 3. Valor padrão ($default)
 */
if (!function_exists('val')) {
    function val($field, $data, $default = '') {
        if (isset($_POST[$field])) {
            return e((string)$_POST[$field]);
        }
        return e((string)($data[$field] ?? $default));
    }
}

// Lógica de garagem
$garagemValue = $_POST['garagem'] ?? ($imovel['garagem'] ?? 'nenhuma');
// Tratar booleano da API ou string do formulário
if ($garagemValue === true || $garagemValue === 1 || $garagemValue === 'sim') {
    $temGaragem = true;
} else {
    $temGaragem = false;
}
$vagas = $_POST['garagem_vagas'] ?? ($imovel['garagem_vagas'] ?? '1');

// Tamanho (remover 'm²' se vier do banco)
$tamanhoNum = $_POST['tamanho_num'] ?? '';
if (!$tamanhoNum && !empty($imovel['tamanho'])) {
    $tamanhoNum = preg_replace('/[^0-9.]/', '', (string)$imovel['tamanho']);
}
?>

<!-- Seção: Dados Básicos -->
<h6 class="mb-3" style="color:#6b7fa3;font-size:11px;text-transform:uppercase;letter-spacing:.06em">Dados do imóvel</h6>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <label class="form-label">Código</label>
        <?php if ($modo === 'editar'): ?>
            <input class="form-control" value="<?= e(substr((string)($imovel['id'] ?? ''), -5)) ?>" disabled title="ID do sistema">
            <input type="hidden" name="codigo" value="<?= val('codigo', $imovel) ?>">
        <?php else: ?>
            <input name="codigo" class="form-control" placeholder="Opcional" value="<?= val('codigo', $imovel) ?>">
        <?php endif; ?>
    </div>
    <div class="col-md-6">
        <label class="form-label">Título <span class="text-danger">*</span></label>
        <input name="titulo" class="form-control" required placeholder="Ex: Casa com Jardim" value="<?= val('titulo', $imovel) ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <?php 
            $statusOptions = [
                'disponivel'  => 'Disponível',
                'locado'      => 'Locado',
                'em_vistoria' => 'Em vistoria',
                'manutencao'  => 'Manutenção'
            ];
            $statusAtual = $_POST['status'] ?? ($imovel['status'] ?? 'disponivel');
            foreach ($statusOptions as $v => $l): 
            ?>
            <option value="<?= $v ?>" <?= $statusAtual === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <label class="form-label">Tipo <span class="text-danger">*</span></label>
        <select name="tipo" class="form-select" required>
            <option value="">Selecione...</option>
            <?php 
            $tipoAtual = $_POST['tipo'] ?? ($imovel['tipo'] ?? '');
            foreach (['Apartamento','Casa','Comercial','Galpão','Terreno'] as $t): 
                $selected = ($tipoAtual === $t) ? 'selected' : '';
            ?>
            <option value="<?= e($t) ?>" <?= $selected ?>><?= e($t) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Tamanho <span class="text-danger">*</span></label>
        <div class="input-group">
            <input type="number" step="0.01" min="1" name="tamanho_num" id="tamanho_num"
                   class="form-control" placeholder="80" required value="<?= e($tamanhoNum) ?>">
            <span class="input-group-text">m²</span>
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label">Garagem</label>
        <div class="d-flex gap-2">
            <select name="garagem" id="select_garagem" class="form-select">
                <option value="nenhuma" <?= !$temGaragem ? 'selected' : '' ?>>Sem garagem</option>
                <option value="sim" <?= $temGaragem ? 'selected' : '' ?>>Com garagem</option>
            </select>
            <input type="number" name="garagem_vagas" id="garagem_vagas" min="1" max="10"
                   class="form-control" style="width:80px;<?= $temGaragem ? '' : 'display:none' ?>"
                   placeholder="Vagas" value="<?= e($vagas) ?>">
        </div>
    </div>
</div>

<!-- Seção: Endereço -->
<h6 class="mb-3" style="color:#6b7fa3;font-size:11px;text-transform:uppercase;letter-spacing:.06em">Endereço</h6>
<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">CEP</label>
        <div class="input-group">
            <input name="cep" id="campo-cep" class="form-control" placeholder="00000-000" maxlength="9"
                   value="<?= val('cep', $end) ?>">
            <button type="button" class="btn btn-outline-secondary" id="btn-buscar-cep">Buscar</button>
        </div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Logradouro</label>
        <?php 
            // O campo no banco pode vir como 'rua' ou 'logradouro'
            $ruaValue = $_POST['rua'] ?? $end['rua'] ?? $end['logradouro'] ?? '';
        ?>
        <input name="rua" id="campo-logradouro" class="form-control" placeholder="Rua das Flores"
               value="<?= e($ruaValue) ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">Número</label>
        <input name="numero" class="form-control" placeholder="123" value="<?= val('numero', $end) ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">Complemento</label>
        <input name="complemento" class="form-control" placeholder="Apto 42" value="<?= val('complemento', $end) ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">Bairro</label>
        <input name="bairro" id="campo-bairro" class="form-control" placeholder="Bairro" value="<?= val('bairro', $end) ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">Cidade</label>
        <input name="cidade" id="campo-cidade" class="form-control" placeholder="São Paulo" value="<?= val('cidade', $end) ?>">
    </div>
    <div class="col-md-1">
        <label class="form-label">UF</label>
        <input name="estado" id="campo-estado" class="form-control" maxlength="2" placeholder="SP" value="<?= val('estado', $end) ?>">
    </div>
</div>

<h6 class="mb-3 mt-4" style="color:#6b7fa3;font-size:11px;text-transform:uppercase;letter-spacing:.06em">Localização no mapa</h6>
<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">Latitude</label>
        <?php
        $latVal = $_POST['latitude'] ?? ($end['latitude'] ?? '');
        $lngVal = $_POST['longitude'] ?? ($end['longitude'] ?? '');
        ?>
        <input type="text" name="latitude" id="campo-latitude" class="form-control" inputmode="decimal"
               placeholder="-23.550520" value="<?= e((string) $latVal) ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">Longitude</label>
        <input type="text" name="longitude" id="campo-longitude" class="form-control" inputmode="decimal"
               placeholder="-46.633308" value="<?= e((string) $lngVal) ?>">
    </div>
    <div class="col-md-6 d-flex align-items-end gap-2 flex-wrap">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-minha-localizacao">
            <i class="bi bi-geo-alt me-1"></i>Usar minha localização
        </button>
        <span class="form-text mb-0">Clique no mapa abaixo para definir o ponto.</span>
    </div>
    <div class="col-12">
        <div id="mapa-picker" style="height:240px;border-radius:8px;border:1px solid var(--oc-border,#dee2e6)"></div>
    </div>
</div>

<!-- Seção: Observações -->
<div class="row g-3 mt-3">
    <div class="col-12">
        <label class="form-label">Observações</label>
        <textarea name="observacoes" class="form-control" rows="3"><?= val('observacoes', $imovel) ?></textarea>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Garagem vagas toggle
    const selectGaragem = document.getElementById('select_garagem');
    const inputVagas = document.getElementById('garagem_vagas');
    if (selectGaragem && inputVagas) {
        selectGaragem.addEventListener('change', function() {
            inputVagas.style.display = this.value === 'sim' ? 'block' : 'none';
        });
    }

    // Busca CEP via ViaCEP
    const btnCep = document.getElementById('btn-buscar-cep');
    if (btnCep) {
        btnCep.addEventListener('click', function() {
            const cepInput = document.getElementById('campo-cep');
            const cep = cepInput.value.replace(/\D/g, '');
            if (cep.length !== 8) { alert('CEP inválido.'); return; }
            
            btnCep.disabled = true;
            btnCep.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            fetch('https://viacep.com.br/ws/' + cep + '/json/')
                .then(r => r.json())
                .then(d => {
                    if (d.erro) { alert('CEP não encontrado.'); return; }
                    document.getElementById('campo-logradouro').value = d.logradouro || '';
                    document.getElementById('campo-bairro').value     = d.bairro || '';
                    document.getElementById('campo-cidade').value     = d.localidade || '';
                    document.getElementById('campo-estado').value     = d.uf || '';
                })
                .catch(() => alert('Erro ao buscar CEP.'))
                .finally(() => {
                    btnCep.disabled = false;
                    btnCep.innerHTML = 'Buscar';
                });
        });
    }

    const mapEl = document.getElementById('mapa-picker');
    const latInput = document.getElementById('campo-latitude');
    const lngInput = document.getElementById('campo-longitude');
    if (mapEl && latInput && lngInput && typeof L !== 'undefined') {
        const parseCoord = (v) => {
            const n = parseFloat(String(v).replace(',', '.'));
            return Number.isFinite(n) ? n : null;
        };
        let lat = parseCoord(latInput.value) ?? -23.5505;
        let lng = parseCoord(lngInput.value) ?? -46.6333;
        const map = L.map('mapa-picker').setView([lat, lng], latInput.value ? 15 : 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        let marker = L.marker([lat, lng], { draggable: true }).addTo(map);

        const setCoords = (newLat, newLng) => {
            latInput.value = newLat.toFixed(6);
            lngInput.value = newLng.toFixed(6);
            if (marker) {
                marker.setLatLng([newLat, newLng]);
            } else {
                marker = L.marker([newLat, newLng], { draggable: true }).addTo(map);
                marker.on('dragend', () => {
                    const p = marker.getLatLng();
                    setCoords(p.lat, p.lng);
                });
            }
        };

        marker.on('dragend', () => {
            const p = marker.getLatLng();
            setCoords(p.lat, p.lng);
        });

        map.on('click', (e) => setCoords(e.latlng.lat, e.latlng.lng));

        const syncFromInputs = () => {
            const la = parseCoord(latInput.value);
            const lo = parseCoord(lngInput.value);
            if (la !== null && lo !== null) {
                marker.setLatLng([la, lo]);
                map.panTo([la, lo]);
            }
        };
        latInput.addEventListener('change', syncFromInputs);
        lngInput.addEventListener('change', syncFromInputs);

        const btnGeo = document.getElementById('btn-minha-localizacao');
        if (btnGeo && navigator.geolocation) {
            btnGeo.addEventListener('click', () => {
                btnGeo.disabled = true;
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        setCoords(pos.coords.latitude, pos.coords.longitude);
                        map.setView([pos.coords.latitude, pos.coords.longitude], 16);
                        btnGeo.disabled = false;
                    },
                    () => {
                        alert('Não foi possível obter sua localização.');
                        btnGeo.disabled = false;
                    },
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            });
        } else if (btnGeo) {
            btnGeo.disabled = true;
        }

        setTimeout(() => map.invalidateSize(), 200);
    }
});
</script>

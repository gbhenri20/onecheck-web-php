<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/api.php';
require_once dirname(__DIR__) . '/includes/auth_api.php';
require_once dirname(__DIR__) . '/config/mock_data.php';

require_once dirname(__DIR__) . '/includes/rbac.php';
api_require_page('dashboard');

// 1. Buscar dados em paralelo (Performance Boost)
$requests = [
    'dash'       => '/dashboard',
    'imoveis'    => '/imoveis?por_pagina=1',
    'contratos'  => '/contratos?por_pagina=1',
    'rec_imoveis'   => '/imoveis?por_pagina=5',
    'rec_contratos' => '/contratos?por_pagina=5'
];
$responses = ApiClient::multi_get($requests);

$dash         = $responses['dash']['dados'] ?? [];
$resImoveis   = $responses['imoveis'];
$resContratos = $responses['contratos'];
$imoveis      = $responses['rec_imoveis']['dados'] ?? [];
$contratos    = $responses['rec_contratos']['dados'] ?? [];

$stats = [
    'imoveis_locados'      => $dash['imoveis_locados']      ?? 0,
    'checklists_pendentes' => $dash['checklists_pendentes'] ?? 0,
    'problemas_abertos'    => $dash['problemas_abertos']    ?? 0,
    'vistorias_agendadas'  => $dash['vistorias_agendadas']  ?? 0,
    'total_imoveis'        => $resImoveis['paginacao']['total']   ?? 0,
    'total_contratos'      => $resContratos['paginacao']['total'] ?? 0,
];

// 2. Gráfico barras — buscar checklists reais agrupados por mês (Otimizado internamente)
$graficoMensal = dashboard_vistorias_mensal();

// Gráfico rosca — dados reais
$locados     = (int)$stats['imoveis_locados'];
$total       = (int)$stats['total_imoveis'];
$resAllImoveis = ApiClient::get('/imoveis', ['por_pagina' => 100]);
$statusCount = ['locado' => 0, 'disponivel' => 0, 'em_vistoria' => 0, 'manutencao' => 0];
foreach (($resAllImoveis['dados'] ?? []) as $im) {
    $st = $im['status'] ?? 'disponivel';
    if (isset($statusCount[$st])) {
        $statusCount[$st]++;
    }
}
$disponiveis = $statusCount['disponivel'];
$graficoStatus = [
    'labels' => ['Locado', 'Disponível', 'Em vistoria', 'Manutenção'],
    'values' => [$statusCount['locado'], $statusCount['disponivel'], $statusCount['em_vistoria'], $statusCount['manutencao']],
    'colors' => ['#22C55E', '#3B82F6', '#FBBF24', '#F87171'],
];

$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';
require ONECHECK_ROOT . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="oc-page-header mb-0">
        <h2>Dashboard</h2>
        <p>Visão geral · <?= date('d/m/Y H:i') ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(base_url('imoveis/novo.php')) ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Novo imóvel
        </a>
    </div>
</div>

<!-- Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <a href="<?= e(base_url('imoveis/index.php')) ?>" class="text-decoration-none">
            <div class="stat-card card h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="stat-label">Total de imóveis</span>
                        <div class="stat-icon icon-blue"><i class="bi bi-building"></i></div>
                    </div>
                    <div class="stat-value"><?= $stats['total_imoveis'] ?></div>
                    <div class="stat-change"><?= $stats['imoveis_locados'] ?> locados</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-xl-3">
        <a href="<?= e(base_url('contratos/index.php')) ?>" class="text-decoration-none">
            <div class="stat-card card h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="stat-label">Contratos ativos</span>
                        <div class="stat-icon icon-amber"><i class="bi bi-file-earmark-text"></i></div>
                    </div>
                    <div class="stat-value"><?= $stats['total_contratos'] ?></div>
                    <div class="stat-change">vigentes</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card card h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="stat-label">Checklists pendentes</span>
                    <div class="stat-icon icon-purple"><i class="bi bi-clipboard-check"></i></div>
                </div>
                <div class="stat-value"><?= $stats['checklists_pendentes'] ?></div>
                <div class="stat-change">aguardando aceite</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card card h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="stat-label">Problemas abertos</span>
                    <div class="stat-icon icon-red"><i class="bi bi-exclamation-triangle"></i></div>
                </div>
                <div class="stat-value"><?= $stats['problemas_abertos'] ?></div>
                <div class="stat-change down">em aberto</div>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos -->
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    Vistorias por mês
                    <div style="font-size:11px;color:#6b7fa3;font-weight:400">Últimos 6 meses</div>
                </div>
                <span class="badge bg-primary">2026</span>
            </div>
            <div class="card-body">
                <div class="chart-legend">
                    <?php 
                    $sumVistorias  = array_sum($graficoMensal['total']);
                    $sumConcluidas = array_sum($graficoMensal['concluidas']);
                    ?>
                    <span><b style="background:#3B82F6"></b>Vistorias (<?= $sumVistorias ?>)</span>
                    <span><b style="background:#22C55E"></b>Concluídas (<?= $sumConcluidas ?>)</span>
                </div>
                <div style="position:relative;height:200px">
                    <canvas id="chartVistorias"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    Status dos imóveis
                    <div style="font-size:11px;color:#6b7fa3;font-weight:400">Distribuição atual</div>
                </div>
                <span class="badge bg-success">Ao vivo</span>
            </div>
            <div class="card-body">
                <div class="chart-legend">
                    <?php foreach ($graficoStatus['labels'] as $i => $label): ?>
                    <span><b style="background:<?= e($graficoStatus['colors'][$i]) ?>"></b><?= e($label) ?> (<?= $graficoStatus['values'][$i] ?>)</span>
                    <?php endforeach; ?>
                </div>
                <div style="position:relative;height:200px" id="chartStatusWrap">
                    <canvas id="chartStatus"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Listas -->
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                Imóveis recentes
                <a href="<?= e(base_url('imoveis/index.php')) ?>" class="badge bg-primary text-decoration-none">Ver todos</a>
            </div>
            <ul class="list-group list-group-flush">
                <?php if (!$imoveis): ?>
                <li class="list-group-item py-3" style="color:#6b7fa3;font-size:13px">
                    <i class="bi bi-building me-2"></i>Nenhum imóvel cadastrado.
                    <a href="<?= e(base_url('imoveis/novo.php')) ?>" class="ms-2" style="color:#4f8ef7">Cadastrar agora →</a>
                </li>
                <?php else: foreach ($imoveis as $im): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                    <div>
                        <span style="color:#e2e8f0;font-size:13px"><?= e($im['tipo'] ?? 'Imóvel') ?> · <?= e($im['tamanho'] ?? '') ?></span>
                        <div style="font-size:11px;color:#6b7fa3;margin-top:2px"><?= e(substr($im['created_at'] ?? '', 0, 10)) ?></div>
                    </div>
                    <?php echo match($im['status'] ?? '') {
                        'locado'      => '<span class="badge bg-success">Locado</span>',
                        'disponivel'  => '<span class="badge bg-primary">Disponível</span>',
                        'em_vistoria' => '<span class="badge bg-warning">Em vistoria</span>',
                        default       => '<span class="badge bg-secondary">' . e($im['status'] ?? '') . '</span>',
                    }; ?>
                </li>
                <?php endforeach; endif; ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                Contratos recentes
                <a href="<?= e(base_url('contratos/index.php')) ?>" class="badge bg-success text-decoration-none">Ver todos</a>
            </div>
            <ul class="list-group list-group-flush">
                <?php if (!$contratos): ?>
                <li class="list-group-item py-3" style="color:#6b7fa3;font-size:13px">
                    <i class="bi bi-file-earmark-text me-2"></i>Nenhum contrato registrado.
                    <a href="<?= e(base_url('contratos/novo.php')) ?>" class="ms-2" style="color:#4f8ef7">Criar agora →</a>
                </li>
                <?php else: foreach ($contratos as $ct): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                    <div>
                        <span style="color:#e2e8f0;font-size:13px">Contrato</span>
                        <div style="font-size:11px;color:#6b7fa3;margin-top:2px">
                            <?= e(substr($ct['data_inicio'] ?? '', 0, 10)) ?> → <?= e(substr($ct['data_fim'] ?? '', 0, 10)) ?>
                        </div>
                    </div>
                    <?php echo match($ct['status'] ?? '') {
                        'ativo'     => '<span class="badge bg-success">Ativo</span>',
                        'encerrado' => '<span class="badge bg-secondary">Encerrado</span>',
                        'cancelado' => '<span class="badge bg-danger">Cancelado</span>',
                        default     => '<span class="badge bg-secondary">' . e($ct['status'] ?? '') . '</span>',
                    }; ?>
                </li>
                <?php endforeach; endif; ?>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const graficoMensal = <?= json_encode($graficoMensal, JSON_UNESCAPED_UNICODE) ?>;
    const graficoStatus = <?= json_encode($graficoStatus, JSON_UNESCAPED_UNICODE) ?>;

    // Registrar plugin globalmente
    Chart.register(ChartDataLabels);

    // Gráfico de barras
    new Chart(document.getElementById('chartVistorias'), {
        type: 'bar',
        data: {
            labels: graficoMensal.labels,
            datasets: [
                { label: 'Vistorias',  data: graficoMensal.total,      backgroundColor: '#3B82F6', borderRadius: 5, barPercentage: 0.6 },
                { label: 'Concluídas', data: graficoMensal.concluidas,  backgroundColor: '#22C55E', borderRadius: 5, barPercentage: 0.6 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                datalabels: {
                    color: '#fff',
                    anchor: 'end',
                    align: 'top',
                    offset: -20,
                    font: { size: 10, weight: 'bold' },
                    formatter: (value) => value > 0 ? value : ''
                }
            },
            scales: {
                x: { ticks: { color: '#94A3B8', font: { size: 11 } }, grid: { color: '#243044' } },
                y: { ticks: { color: '#94A3B8', font: { size: 11 } }, grid: { color: '#243044' }, beginAtZero: true }
            }
        }
    });

    // Gráfico de rosca — exibe placeholder se vazio
    const totalStatus = graficoStatus.values.reduce((a, b) => a + b, 0);
    if (totalStatus === 0) {
        document.getElementById('chartStatusWrap').innerHTML =
            '<div style="height:200px;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:8px">' +
            '<i class="bi bi-building" style="font-size:36px;color:#2a3347"></i>' +
            '<span style="font-size:12px;color:#4a5568">Nenhum imóvel cadastrado</span>' +
            '</div>';
    } else {
        new Chart(document.getElementById('chartStatus'), {
            type: 'doughnut',
            data: {
                labels: graficoStatus.labels,
                datasets: [{ data: graficoStatus.values, backgroundColor: graficoStatus.colors, borderWidth: 0, hoverOffset: 6 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '70%',
                plugins: { 
                    legend: { display: false },
                    datalabels: {
                        color: '#fff',
                        font: { size: 11, weight: 'bold' },
                        formatter: (value) => value > 0 ? value : ''
                    }
                }
            },
            plugins: [{
                id: 'centerText',
                afterDraw: (chart) => {
                    const { ctx, width, height } = chart;
                    ctx.save();
                    ctx.font = 'bold 24px sans-serif';
                    ctx.fillStyle = '#fff';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(totalStatus, width / 2, height / 2 - 10);
                    
                    ctx.font = '11px sans-serif';
                    ctx.fillStyle = '#6b7fa3';
                    ctx.fillText('TOTAL', width / 2, height / 2 + 15);
                    ctx.restore();
                }
            }]
        });
    }
});
</script>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>

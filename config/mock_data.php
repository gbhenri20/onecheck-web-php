<?php
/**
 * ONECHECK — Dados Mockados
 * 
 * Este arquivo centraliza todos os dados simulados do sistema.
 * Quando a API real estiver pronta, basta substituir cada função
 * abaixo pela chamada HTTP correspondente — o restante do sistema
 * não precisa mudar.
 *
 * Para ativar dados reais de uma seção, mude a flag:
 *   define('MOCK_VISTORIAS_MENSAL', false);
 * e implemente a query/chamada de API na função correspondente.
 */

// ============================================================
// FLAGS — true = usa mock | false = usa dado real
// ============================================================
define('MOCK_VISTORIAS_MENSAL',   true);
define('MOCK_STATUS_IMOVEIS',     true);
define('MOCK_PROBLEMAS_RECENTES', true);
define('MOCK_VISTORIAS_RECENTES', true);
define('MOCK_AGENDAMENTOS',       true);

// ============================================================
// VISTORIAS POR MÊS (gráfico de barras)
// Retorna labels e dois datasets: total e concluídas
// API futura: GET /api/relatorios/vistorias-mensal?meses=6
// ============================================================
function mock_vistorias_mensal(): array {
    if (!MOCK_VISTORIAS_MENSAL) {
        // TODO: substituir pela chamada real
        // $response = api_get('/api/relatorios/vistorias-mensal?meses=6');
        // return $response;
    }

    return [
        'labels'     => ['Dez', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai'],
        'total'      => [14, 18, 22, 19, 27, 27],
        'concluidas' => [12, 16, 20, 17, 24, 23],
    ];
}

// ============================================================
// STATUS DOS IMÓVEIS (gráfico de rosca)
// API futura: GET /api/relatorios/imoveis-status
// ============================================================
function mock_status_imoveis(): array {
    if (!MOCK_STATUS_IMOVEIS) {
        // TODO: substituir pela chamada real
        // $response = api_get('/api/relatorios/imoveis-status');
        // return $response;
    }

    return [
        'labels' => ['Locado', 'Disponível', 'Em vistoria', 'Manutenção'],
        'values' => [34, 8, 4, 2],
        'colors' => ['#4ade80', '#4f8ef7', '#fbbf24', '#f87171'],
    ];
}

// ============================================================
// PROBLEMAS RECENTES (lista no dashboard)
// API futura: GET /api/problemas?status=aberto&limit=5&order=prioridade
// ============================================================
function mock_problemas_recentes(): array {
    if (!MOCK_PROBLEMAS_RECENTES) {
        // TODO: substituir pela chamada real
    }

    return [
        ['id' => 1, 'titulo' => 'Infiltração no teto',  'codigo' => 'IM-004', 'prioridade' => 'urgente', 'tempo' => 'há 2h'],
        ['id' => 2, 'titulo' => 'Porta com defeito',    'codigo' => 'IM-011', 'prioridade' => 'alta',    'tempo' => 'há 1d'],
        ['id' => 3, 'titulo' => 'Tomada sem energia',   'codigo' => 'IM-017', 'prioridade' => 'alta',    'tempo' => 'há 2d'],
        ['id' => 4, 'titulo' => 'Torneira pingando',    'codigo' => 'IM-008', 'prioridade' => 'normal',  'tempo' => 'há 3d'],
    ];
}

// ============================================================
// VISTORIAS RECENTES (lista no dashboard)
// API futura: GET /api/vistorias?limit=5&order=recente
// ============================================================
function mock_vistorias_recentes(): array {
    if (!MOCK_VISTORIAS_RECENTES) {
        // TODO: substituir pela chamada real
    }

    return [
        ['id' => 22, 'tipo' => 'inicial',    'codigo' => 'IM-022', 'status' => 'concluida',  'tempo' => 'Hoje às 09:14'],
        ['id' => 21, 'tipo' => 'final',      'codigo' => 'IM-015', 'status' => 'concluida',  'tempo' => 'Ontem às 14:30'],
        ['id' => 20, 'tipo' => 'periodica',  'codigo' => 'IM-031', 'status' => 'em_analise', 'tempo' => 'Ontem às 11:00'],
        ['id' => 19, 'tipo' => 'inicial',    'codigo' => 'IM-019', 'status' => 'agendada',   'tempo' => '22/05 às 16:45'],
    ];
}

// ============================================================
// AGENDAMENTOS FUTUROS
// API futura: GET /api/agendamentos?periodo=proximos30dias
// ============================================================
function mock_agendamentos(): array {
    if (!MOCK_AGENDAMENTOS) {
        // TODO: substituir pela chamada real
    }

    return [
        ['id' => 1, 'codigo' => 'IM-005', 'tipo' => 'inicial',   'data' => '28/05/2026', 'hora' => '09:00'],
        ['id' => 2, 'codigo' => 'IM-012', 'tipo' => 'final',     'data' => '29/05/2026', 'hora' => '14:00'],
        ['id' => 3, 'codigo' => 'IM-023', 'tipo' => 'periodica', 'data' => '30/05/2026', 'hora' => '10:30'],
    ];
}

// ============================================================
// HELPER — badge de prioridade
// ============================================================
function mock_badge_prioridade(string $prioridade): string {
    return match($prioridade) {
        'urgente' => '<span class="badge bg-danger">Urgente</span>',
        'alta'    => '<span class="badge bg-warning">Alta</span>',
        'normal'  => '<span class="badge bg-primary">Normal</span>',
        default   => '<span class="badge bg-secondary">' . e($prioridade) . '</span>',
    };
}

// ============================================================
// HELPER — badge de status de vistoria
// ============================================================
function mock_badge_vistoria(string $status): string {
    return match($status) {
        'concluida'  => '<span class="badge bg-success">Concluída</span>',
        'em_analise' => '<span class="badge bg-warning">Em análise</span>',
        'agendada'   => '<span class="badge bg-primary">Agendada</span>',
        'cancelada'  => '<span class="badge bg-secondary">Cancelada</span>',
        default      => '<span class="badge bg-secondary">' . e($status) . '</span>',
    };
}

// ============================================================
// VISTORIAS MENSAIS REAIS (gráfico de barras)
// Busca checklists dos últimos 6 meses agrupados por mês
// ============================================================
function dashboard_vistorias_mensal(): array
{
    // Montar os últimos 6 meses dinamicamente
    $labels     = [];
    $total      = [];
    $concluidas = [];
    $mesesPt    = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    $periodos   = [];

    for ($i = 5; $i >= 0; $i--) {
        $ts      = strtotime("-{$i} months");
        $mes     = (int) date('n', $ts);
        $ano     = (int) date('Y', $ts);
        $labels[] = $mesesPt[$mes - 1];
        $periodos[] = ['mes' => $mes, 'ano' => $ano];
        $total[] = 0;
        $concluidas[] = 0;
    }

    // OTIMIZAÇÃO: Buscar todos os contratos uma única vez
    $res = ApiClient::get('/contratos', ['por_pagina' => 100]);
    $contratos = $res['dados'] ?? [];

    if (empty($contratos)) {
        return ['labels' => $labels, 'total' => array_fill(0, 6, 0), 'concluidas' => array_fill(0, 6, 0)];
    }

    // OTIMIZAÇÃO: Buscar todos os checklists de todos os contratos EM PARALELO
    $reqChecklists = [];
    foreach ($contratos as $ct) {
        $reqChecklists[$ct['id']] = '/contratos/' . $ct['id'] . '/checklists';
    }
    $resChecklists = ApiClient::multi_get($reqChecklists);

    foreach ($resChecklists as $contratoId => $resp) {
        foreach (($resp['dados'] ?? []) as $ck) {
            $dataCk = $ck['created_at'] ?? $ck['data_vistoria'] ?? '';
            if (!$dataCk) continue;
            
            $tsCk  = strtotime($dataCk);
            $ckMes = (int) date('n', $tsCk);
            $ckAno = (int) date('Y', $tsCk);

            // Verificar em qual dos 6 meses se encaixa
            foreach ($periodos as $idx => $p) {
                if ($ckMes === $p['mes'] && $ckAno === $p['ano']) {
                    $total[$idx]++;
                    if (($ck['status'] ?? '') === 'aceito' || ($ck['status'] ?? '') === 'pendente_aceite') {
                        $concluidas[$idx]++;
                    }
                    break;
                }
            }
        }
    }

    return [
        'labels'     => $labels,
        'total'      => $total,
        'concluidas' => $concluidas,
    ];
}

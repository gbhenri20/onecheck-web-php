<?php

declare(strict_types=1);

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_render(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $map = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'];
    $cls = $map[$f['type']] ?? 'info';
    echo '<div class="alert alert-' . e($cls) . ' alert-dismissible fade show" data-auto-dismiss role="alert">';
    echo e($f['message']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

function page_header(string $title, string $subtitle = '', ?string $actionsHtml = null): void
{
    echo '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">';
    echo '<div><h1 class="h3 mb-1">' . e($title) . '</h1>';
    if ($subtitle !== '') {
        echo '<p class="text-muted mb-0">' . e($subtitle) . '</p>';
    }
    echo '</div>';
    if ($actionsHtml) {
        echo '<div class="d-flex flex-wrap gap-2">' . $actionsHtml . '</div>';
    }
    echo '</div>';
}

function badge_status(string $grupo, string $valor): string
{
    $cores = [
        'imovel' => [
            'disponivel' => 'success', 'locado' => 'primary', 'ocupado' => 'primary',
            'em_vistoria' => 'info', 'manutencao' => 'warning', 'inativo' => 'secondary',
        ],
        'vistoria' => [
            'rascunho' => 'secondary', 'em_andamento' => 'info', 'concluida' => 'success', 'cancelada' => 'dark',
        ],
        'contrato' => [
            'rascunho' => 'secondary', 'ativo' => 'success', 'encerrado' => 'dark', 'cancelado' => 'danger',
        ],
        'problema' => [
            'aberto' => 'danger', 'em_analise' => 'warning', 'resolvido' => 'success', 'cancelado' => 'secondary',
        ],
        'prioridade' => [
            'baixa' => 'secondary', 'media' => 'info', 'alta' => 'warning', 'urgente' => 'danger',
        ],
        'perfil' => [
            'admin' => 'danger', 'gestor' => 'primary', 'vistoriador' => 'info', 'visualizador' => 'secondary',
        ],
    ];
    $cor = $cores[$grupo][$valor] ?? 'secondary';
    $label = str_replace('_', ' ', $valor);
    return '<span class="badge text-bg-' . e($cor) . '">' . e(ucfirst($label)) . '</span>';
}

function format_money(float $v): string
{
    return 'R$ ' . number_format($v, 2, ',', '.');
}

function format_date(?string $d): string
{
    if (!$d) {
        return '—';
    }
    return date('d/m/Y', strtotime($d));
}

function format_datetime(?string $d): string
{
    if (!$d) {
        return '—';
    }
    return date('d/m/Y H:i', strtotime($d));
}

function get_int(string $key): int
{
    return (int) ($_GET[$key] ?? 0);
}

function get_str(string $key): string
{
    return trim($_GET[$key] ?? '');
}

function post_str(string $key): string
{
    return trim($_POST[$key] ?? '');
}

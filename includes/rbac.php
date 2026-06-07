<?php
/**
 * Controle de acesso por perfil (RBAC) — web + API session
 */

function api_role(): string
{
    return strtolower((string) ($_SESSION['user']['role'] ?? 'visualizador'));
}

function api_is_admin(): bool
{
    return api_role() === 'admin';
}

/** URL inicial após login conforme perfil */
function api_home_url(): string
{
    return api_role() === 'locatario'
        ? base_url('locatario/index.php')
        : base_url('dashboard/index.php');
}

/** Perfis permitidos por área do sistema */
function api_page_roles(): array
{
    return [
        'dashboard'  => ['admin', 'gestor', 'vistoriador', 'visualizador'],
        'imoveis'    => ['admin', 'gestor', 'vistoriador', 'visualizador'],
        'vistorias'  => ['admin', 'gestor', 'vistoriador', 'visualizador', 'locatario'],
        'contratos'  => ['admin', 'gestor', 'visualizador'],
        'problemas'  => ['admin', 'gestor', 'vistoriador', 'visualizador', 'locatario'],
        'mapa'       => ['admin', 'gestor', 'vistoriador', 'visualizador'],
        'usuarios'   => ['admin', 'gestor'],
        'logs'       => ['admin'],
        'locatario'  => ['locatario'],
    ];
}

function api_can_access(string $page): bool
{
    if (api_is_admin()) {
        return true;
    }
    $roles = api_page_roles()[$page] ?? [];
    return in_array(api_role(), $roles, true);
}

function api_require_page(string $page): void
{
    api_require_login();
    if (!api_can_access($page)) {
        flash_set('error', 'Você não tem permissão para acessar esta área.');
        redirect(api_home_url());
    }
}

function api_can_create(string $resource): bool
{
    if (api_is_admin()) {
        return true;
    }
    $matrix = [
        'imoveis'    => ['admin', 'gestor'],
        'contratos'  => ['admin', 'gestor'],
        'vistorias'  => ['admin', 'gestor', 'vistoriador'],
        'problemas'  => ['admin', 'gestor', 'vistoriador', 'locatario'],
        'usuarios'   => ['admin'],
    ];
    return in_array(api_role(), $matrix[$resource] ?? [], true);
}

/** Itens do menu lateral filtrados por perfil */
function api_menu_items(): array
{
    $items = [
        ['section' => 'Principal'],
        ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'url' => 'dashboard/index.php', 'page' => 'dashboard'],
        ['id' => 'imoveis', 'label' => 'Imóveis', 'icon' => 'bi-building', 'url' => 'imoveis/index.php', 'page' => 'imoveis'],
        ['id' => 'vistorias', 'label' => 'Vistorias', 'icon' => 'bi-camera', 'url' => 'vistorias/index.php', 'page' => 'vistorias'],
        ['id' => 'contratos', 'label' => 'Contratos', 'icon' => 'bi-file-earmark-text', 'url' => 'contratos/index.php', 'page' => 'contratos'],
        ['section' => 'Gestão'],
        ['id' => 'problemas', 'label' => 'Problemas', 'icon' => 'bi-exclamation-triangle', 'url' => 'problemas/index.php', 'page' => 'problemas'],
        ['id' => 'mapa', 'label' => 'Mapa', 'icon' => 'bi-map', 'url' => 'imoveis/mapa.php', 'page' => 'mapa'],
        ['id' => 'usuarios', 'label' => 'Usuários', 'icon' => 'bi-people', 'url' => 'usuarios/index.php', 'page' => 'usuarios'],
        ['section' => 'Sistema'],
        ['id' => 'logs', 'label' => 'Logs', 'icon' => 'bi-list-ul', 'url' => 'dashboard/logs.php', 'page' => 'logs'],
    ];

    $out = [];
    $lastSection = null;
    foreach ($items as $item) {
        if (isset($item['section'])) {
            $lastSection = $item;
            continue;
        }
        if (!api_can_access($item['page'])) {
            continue;
        }
        if ($lastSection !== null) {
            $out[] = $lastSection;
            $lastSection = null;
        }
        $out[] = $item;
    }
    return $out;
}

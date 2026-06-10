<?php
/**
 * Escopo de dados por perfil + lookups para listagens.
 */

function api_user_id(): string
{
    return (string) ($_SESSION['user']['id'] ?? '');
}

function api_can_accept_vistoria(): bool
{
    return in_array(api_role(), ['admin', 'gestor'], true);
}

/** @return array<string, array> */
function api_fetch_lookups(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $resIm = ApiClient::get('/imoveis', ['por_pagina' => 100, 'com_endereco' => 1]);
    $resUs = ApiClient::get('/usuarios', ['por_pagina' => 100]);

    $imoveis = [];
    foreach (($resIm['dados'] ?? []) as $im) {
        $imoveis[$im['id']] = $im;
    }
    $usuarios = [];
    foreach (($resUs['dados'] ?? []) as $u) {
        $usuarios[$u['id']] = $u;
    }

    $cache = ['imoveis' => $imoveis, 'usuarios' => $usuarios];
    return $cache;
}

function api_imovel_label(?array $im): string
{
    if (!$im) {
        return '—';
    }
    $parts = array_filter([
        $im['codigo'] ?? null,
        $im['titulo'] ?? null,
        $im['tipo'] ?? null,
    ]);
    return $parts ? implode(' · ', $parts) : substr($im['id'] ?? '', 0, 8);
}

function api_usuario_label(?array $u): string
{
    if (!$u) {
        return '—';
    }
    return trim(($u['nome'] ?? '') . ' (' . ($u['email'] ?? '') . ')');
}

function api_filter_contratos(array $contratos): array
{
    $role = api_role();
    $uid = api_user_id();
    if ($role === 'admin' || $role === 'gestor' || $role === 'visualizador') {
        return $contratos;
    }
    if ($role === 'locatario') {
        return array_values(array_filter($contratos, fn($c) => ($c['locatario_id'] ?? '') === $uid));
    }
    if ($role === 'vistoriador') {
        $mine = api_vistoriador_contrato_ids($contratos);
        return array_values(array_filter($contratos, fn($c) => in_array($c['id'] ?? '', $mine, true)));
    }
    return $contratos;
}

/** IDs de contratos com checklist do vistoriador logado. */
function api_vistoriador_contrato_ids(?array $contratos = null): array
{
    static $ids = null;
    if ($ids !== null) {
        return $ids;
    }
    if (api_role() !== 'vistoriador') {
        $ids = [];
        return $ids;
    }
    $uid = api_user_id();
    if ($contratos === null) {
        $contratos = ApiClient::get('/contratos', ['por_pagina' => 100])['dados'] ?? [];
    }
    $found = [];
    $requests = [];
    foreach ($contratos as $ct) {
        $requests[$ct['id']] = '/contratos/' . $ct['id'] . '/checklists';
    }
    if (!$requests) {
        $ids = [];
        return $ids;
    }
    foreach (ApiClient::multi_get($requests) as $cid => $res) {
        foreach (($res['dados'] ?? []) as $ck) {
            if (($ck['vistoriador_id'] ?? '') === $uid) {
                $found[$cid] = true;
            }
        }
    }
    $ids = array_keys($found);
    return $ids;
}

function api_filter_checklists(array $checklists, array $contratosById = []): array
{
    $role = api_role();
    $uid = api_user_id();
    if ($role === 'admin' || $role === 'gestor' || $role === 'visualizador') {
        return $checklists;
    }
    if ($role === 'vistoriador') {
        return array_values(array_filter($checklists, fn($c) => ($c['vistoriador_id'] ?? '') === $uid));
    }
    if ($role === 'locatario') {
        return array_values(array_filter($checklists, function ($c) use ($contratosById, $uid) {
            $cid = $c['_contrato_id'] ?? $c['contrato_id'] ?? '';
            $ct = $contratosById[$cid] ?? null;
            return $ct && ($ct['locatario_id'] ?? '') === $uid;
        }));
    }
    return $checklists;
}

function api_filter_imoveis(array $imoveis): array
{
    $role = api_role();
    if ($role === 'admin' || $role === 'gestor' || $role === 'visualizador') {
        return $imoveis;
    }
    $lookups = api_fetch_lookups();
    $contratos = api_filter_contratos(ApiClient::get('/contratos', ['por_pagina' => 100])['dados'] ?? []);
    $imovelIds = array_unique(array_column($contratos, 'imovel_id'));
    return array_values(array_filter($imoveis, fn($im) => in_array($im['id'] ?? '', $imovelIds, true)));
}

function api_load_scoped_contratos(array $params = []): array
{
    $params += ['por_pagina' => 100];
    $res = ApiClient::get('/contratos', $params);
    $res['dados'] = api_filter_contratos($res['dados'] ?? []);
    return $res;
}

function api_load_scoped_checklists(): array
{
    $contratos = api_load_scoped_contratos()['dados'] ?? [];
    $byId = [];
    foreach ($contratos as $ct) {
        $byId[$ct['id']] = $ct;
    }
    if (!$contratos) {
        return [];
    }
    $requests = [];
    foreach ($contratos as $ct) {
        $requests[$ct['id']] = '/contratos/' . $ct['id'] . '/checklists';
    }
    $checklists = [];
    foreach (ApiClient::multi_get($requests) as $contratoId => $resC) {
        foreach (($resC['dados'] ?? []) as $c) {
            $c['_contrato_id'] = $contratoId;
            $checklists[] = $c;
        }
    }
    return api_filter_checklists($checklists, $byId);
}

function api_excerpt(?string $text, int $max = 80): string
{
    $text = trim((string) $text);
    if ($text === '') {
        return '—';
    }
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    return mb_substr($text, 0, $max - 1) . '…';
}

function api_endereco_label(?array $end): string
{
    if (!$end) {
        return '—';
    }
    $rua = $end['rua'] ?? $end['logradouro'] ?? '';
    $parts = array_filter([
        trim($rua . ($end['numero'] ?? '' ? ', ' . $end['numero'] : '')),
        $end['bairro'] ?? null,
        $end['cidade'] ?? null,
    ]);
    return $parts ? implode(' · ', $parts) : '—';
}

function api_role_label(string $role): string
{
    return match ($role) {
        'admin'        => 'Admin',
        'gestor'       => 'Gestor',
        'vistoriador'  => 'Vistoriador',
        'locatario'    => 'Locatário',
        'visualizador' => 'Visualizador',
        default        => ucfirst($role),
    };
}

function api_mfa_ativo(array $u): bool
{
    if (array_key_exists('mfa_ativo', $u)) {
        return (bool) $u['mfa_ativo'];
    }
    return !empty($u['mfa_configurado']);
}

function api_mfa_configurado(array $u): bool
{
    if (array_key_exists('mfa_configurado', $u)) {
        return (bool) $u['mfa_configurado'];
    }
    return api_mfa_ativo($u);
}

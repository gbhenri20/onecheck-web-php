<?php
require_once ONECHECK_ROOT . '/config/api.php';
require_once ONECHECK_ROOT . '/includes/auth_api.php';
require_once ONECHECK_ROOT . '/includes/rbac.php';

$pageTitle    = $pageTitle ?? 'OneCheck';
$activeMenu   = $activeMenu ?? '';
$portalLayout = $portalLayout ?? false;
$user         = api_user();
$initials     = api_initials();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · OneCheck</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <?php if (!$portalLayout): ?>
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
    <?php endif; ?>
    <link href="<?= e(asset_url('css/style.css')) ?>" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (!$portalLayout): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <?php endif; ?>
</head>
<body class="app-body">
<?php if ($portalLayout): ?>
<nav class="navbar app-navbar">
    <div class="container">
        <a class="navbar-brand mb-0 text-decoration-none" href="<?= e(base_url('locatario/index.php')) ?>">
            <span class="brand-one">One</span><span class="brand-check">Check</span>
            <span class="text-muted small ms-2">Locatário</span>
        </a>
        <div class="d-flex gap-2 align-items-center">
            <span class="text-muted small d-none d-sm-inline"><?= e($user['nome'] ?? '') ?></span>
            <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('public/logout.php')) ?>">Sair</a>
        </div>
    </div>
</nav>
<main class="container py-4 app-main">
<?php else: ?>
<?php require ONECHECK_ROOT . '/includes/navbar.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php require ONECHECK_ROOT . '/includes/sidebar.php'; ?>
        <main class="col-lg-10 col-xl-10 ms-auto px-4 py-4 app-main">
<?php endif; ?>

<?php
/** @var string $pageTitle */
$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Locatário') ?> · OneCheck</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset_url('css/style.css')) ?>" rel="stylesheet">
</head>
<body class="app-body">
<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <span class="navbar-brand mb-0 h1">OneCheck · Locatário</span>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('usuarios/perfil.php')) ?>"><?= e($user['nome']) ?></a>
            <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('public/logout.php')) ?>">Sair</a>
        </div>
    </div>
</nav>
<main class="container py-4">
<?php flash_render(); ?>

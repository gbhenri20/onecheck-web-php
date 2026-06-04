<nav class="navbar app-navbar">
    <div class="container-fluid">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-light btn-sm d-lg-none" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#sidebarMobile">
                <i class="bi bi-list"></i>
            </button>
            <a class="navbar-brand" href="<?= e(base_url('dashboard/index.php')) ?>"
               style="font-weight:700;letter-spacing:-.3px;font-size:16px">
                <span class="brand-one">One</span><span class="brand-check">Check</span>
            </a>
        </div>
        <?php $u = api_user(); if ($u): ?>
        <div class="d-flex align-items-center gap-2">
            <span class="d-none d-md-inline" style="font-size:12px;color:#6b7fa3"><?= e($u['nome'] ?? '') ?></span>
            <div class="oc-avatar"><?= e(api_initials()) ?></div>
            <a class="btn btn-outline-light btn-sm" href="<?= e(base_url('public/logout.php')) ?>">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
        <?php endif; ?>
    </div>
</nav>

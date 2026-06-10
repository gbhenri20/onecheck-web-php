<nav class="navbar app-navbar">
    <div class="container-fluid">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-light btn-sm d-lg-none" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#sidebarMobile">
                <i class="bi bi-list"></i>
            </button>
            <a class="navbar-brand" href="<?= e(api_home_url()) ?>"
               style="font-weight:700;letter-spacing:-.3px;font-size:16px">
                <span class="brand-one">One</span><span class="brand-check">Check</span>
            </a>
        </div>
        <?php $u = api_user(); if ($u): ?>
        <?php require ONECHECK_ROOT . '/includes/user_menu.php'; ?>
        <?php endif; ?>
    </div>
</nav>

<?php
$menu = api_menu_items();

function sidebar_nav(string $activeMenu): void
{
    global $menu;
    foreach ($menu as $item) {
        if (isset($item['section'])) {
            echo '<div class="nav-section">' . e($item['section']) . '</div>';
        } else {
            $active = $activeMenu === $item['id'] ? 'active' : '';
            echo '<a class="nav-link ' . $active . '" href="' . e(base_url($item['url'])) . '">';
            echo '<i class="bi ' . e($item['icon']) . '"></i>' . e($item['label']) . '</a>';
        }
    }
}
?>
<aside class="col-lg-2 col-xl-2 d-none d-lg-block app-sidebar">
    <nav class="nav flex-column">
        <?php sidebar_nav($activeMenu); ?>
    </nav>
</aside>

<div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="sidebarMobile">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">
            <span class="brand-one">One</span><span class="brand-check">Check</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body pt-0">
        <nav class="nav flex-column">
            <?php sidebar_nav($activeMenu); ?>
        </nav>
    </div>
</div>

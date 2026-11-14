<?php
$current_path = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$current_module = basename(dirname($current_path));
$is_dashboard = basename($current_path) === 'index.php'
    && !in_array($current_module, ['farmers', 'farms', 'crops', 'planting', 'harvest', 'reports'], true);
?>
<aside class="sidebar">
    <a class="sidebar-brand" href="<?= BASE_URL ?>/index.php">
        <span class="brand-icon"><i class="bi bi-flower1"></i></span>
        <span><strong>Agriculture</strong><small>Information Systems</small></span>
    </a>
    <nav class="sidebar-menu" aria-label="Main navigation">
        <p class="menu-label">WORKSPACE</p>
        <a href="<?= BASE_URL ?>/index.php"
           class="nav-link <?= $is_dashboard ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
        </a>
        <p class="menu-label">RECORDS</p>
        <a href="<?= BASE_URL ?>/crops/index.php"
           class="nav-link <?= $current_module === 'crops' ? 'active' : '' ?>">
            <i class="bi bi-flower2"></i><span>Crops</span>
        </a>
        <a href="<?= BASE_URL ?>/farmers/index.php"
           class="nav-link <?= $current_module === 'farmers' ? 'active' : '' ?>">
            <i class="bi bi-people"></i><span>Farmers</span>
        </a>
        <a href="<?= BASE_URL ?>/farms/index.php"
           class="nav-link <?= $current_module === 'farms' ? 'active' : '' ?>">
            <i class="bi bi-house"></i><span>Farms</span>
        </a>
        <a href="<?= BASE_URL ?>/planting/index.php"
           class="nav-link <?= $current_module === 'planting' ? 'active' : '' ?>">
            <i class="bi bi-calendar2-check"></i><span>Planting Records</span>
        </a>
        <a href="<?= BASE_URL ?>/harvest/index.php"
           class="nav-link <?= $current_module === 'harvest' ? 'active' : '' ?>">
            <i class="bi bi-basket"></i><span>Harvest Records</span>
        </a>
        <a href="<?= BASE_URL ?>/reports/index.php"
           class="nav-link <?= $current_module === 'reports' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-bar-graph"></i><span>Reports</span>
        </a>
    </nav>
    <div class="sidebar-bottom">
        <div class="system-info mb-3"><i class="bi bi-person-circle"></i><span><?= htmlspecialchars($_SESSION['auth_user'] ?? '') ?> · <?= htmlspecialchars($_SESSION['auth_role'] ?? '') ?></span></div>
        <form method="post" action="<?= BASE_URL ?>/logout.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(auth_csrf_token()) ?>">
            <button class="nav-link w-100 text-start" type="submit"><i class="bi bi-box-arrow-right"></i><span>Sign out</span></button>
        </form>
    </div>
</aside>

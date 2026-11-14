<div class="app-shell">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div>
                <p class="eyebrow">AGRICULTURE INFORMATION SYSTEMS</p>
                <h1><?= htmlspecialchars($module_title) ?></h1>
                <p class="topbar-subtitle"><?= htmlspecialchars($module_description) ?></p>
            </div>
            <?php if (!empty($module_action_url)): ?>
                <a class="btn btn-success" href="<?= htmlspecialchars($module_action_url) ?>">
                    <i class="bi bi-plus-lg me-1"></i>
                    <?= htmlspecialchars($module_action_label ?? 'Add Record') ?>
                </a>
            <?php endif; ?>
        </header>
        <div class="page-content">

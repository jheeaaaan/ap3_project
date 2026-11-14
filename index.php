<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$page_title = 'Dashboard';
$counts = [];
$dashboard_error = '';

try {
    $pdo = require __DIR__ . '/config/database.php';
    $tables = [
        'farmer' => 'Farmers',
        'farm' => 'Farms',
        'crop' => 'Crops',
        'planting' => 'Planting Records',
        'harvest' => 'Harvest Records',
    ];

    foreach ($tables as $table => $label) {
        $counts[$label] = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    }

    $recent_plantings = $pdo->query(
        'SELECT planting.planting_id, planting.planting_date, farm.farm_name,
                crop.crop_name, planting_status.status_name
         FROM planting
         JOIN farm ON farm.farm_id = planting.farm_id
         JOIN crop ON crop.crop_id = planting.crop_id
         JOIN planting_status ON planting_status.status_id = planting.status_id
         ORDER BY planting.planting_date DESC, planting.planting_id DESC
         LIMIT 5'
    )->fetchAll();

    $recent_harvests = $pdo->query(
        'SELECT harvest.harvest_id, harvest.harvest_date, harvest.quantity_kg,
                farm.farm_name, crop.crop_name
         FROM harvest
         JOIN planting ON planting.planting_id = harvest.planting_id
         JOIN farm ON farm.farm_id = planting.farm_id
         JOIN crop ON crop.crop_id = planting.crop_id
         ORDER BY harvest.harvest_date DESC, harvest.harvest_id DESC
         LIMIT 5'
    )->fetchAll();

    $plantings_by_status = $pdo->query(
        'SELECT planting_status.status_name,
                COUNT(planting.planting_id) AS total
         FROM planting_status
         LEFT JOIN planting ON planting.status_id = planting_status.status_id
         GROUP BY planting_status.status_id, planting_status.status_name
         ORDER BY planting_status.status_name'
    )->fetchAll();

    $harvested_quantity = (float) $pdo->query(
        'SELECT COALESCE(SUM(quantity_kg), 0) FROM harvest'
    )->fetchColumn();
} catch (Throwable $e) {
    // Do not expose connection details or display invented totals.
    $dashboard_error = 'Dashboard totals are unavailable. Check the database connection.';
}

include __DIR__ . '/includes/header.php';
?>

<div class="app-shell">
    <?php
require_once __DIR__ . '/includes/auth.php';
require_login();
 include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <p class="eyebrow">AGRICULTURE INFORMATION SYSTEMS</p>
                <h1>Dashboard</h1>
                <p class="topbar-subtitle">A clear view of your farm records and operations.</p>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <?php $isAdmin = ($_SESSION['auth_role'] ?? '') === 'Administrator'; ?>
                <span class="status-pill" style="<?= $isAdmin ? '' : 'background:#f2e8d8;color:#654b35;' ?>">
                    <i class="bi <?= $isAdmin ? 'bi-shield-lock' : 'bi-person-badge' ?>"></i>
                    <?= $isAdmin ? 'Administrator &middot; Full access' : 'Staff &middot; Limited access (no deleting)' ?>
                </span>
                <span class="status-pill <?= $dashboard_error === '' ? '' : 'status-pill-offline' ?>">
                    <i class="bi <?= $dashboard_error === '' ? 'bi-circle-fill' : 'bi-exclamation-circle' ?>"></i>
                    <?= $dashboard_error === '' ? 'Supabase connected' : 'Database unavailable' ?>
                </span>
            </div>
        </header>

        <div class="page-content">
            <?php if ($dashboard_error !== ''): ?>
                <div class="alert alert-warning" role="alert">
                    <?= htmlspecialchars($dashboard_error) ?>
                </div>
            <?php endif; ?>

            <section class="welcome-section">
                <div>
                    <span class="welcome-kicker">WELCOME</span>
                    <h2>Manage your agricultural records</h2>
                    <p>Keep farmer, farm, and crop information organized in one place.</p>
                </div>
                <i class="bi bi-sunrise welcome-art" aria-hidden="true"></i>
            </section>

            <h2 class="section-heading">Record overview</h2>
            <div class="row g-3 mb-4">
                <?php
                $summary_cards = [
                    ['Crops', 'bi-flower2', 'crops/index.php'],
                    ['Farmers', 'bi-people', 'farmers/index.php'],
                    ['Farms', 'bi-house', 'farms/index.php'],
                    ['Planting Records', 'bi-calendar2-check', 'planting/index.php'],
                    ['Harvest Records', 'bi-basket', 'harvest/index.php'],
                ];
                ?>
                <?php foreach ($summary_cards as [$label, $icon, $url]): ?>
                    <div class="col-sm-6 col-xl-4">
                        <a class="dashboard-card stat-card" href="<?= BASE_URL ?>/<?= $url ?>">
                            <span class="card-icon"><i class="bi <?= $icon ?>"></i></span>
                            <span class="stat-label">Total <?= htmlspecialchars($label) ?></span>
                            <strong class="stat-value">
                                <?= isset($counts[$label]) ? number_format($counts[$label]) : 'â€”' ?>
                            </strong>
                            <span class="stat-link">View records <i class="bi bi-arrow-right"></i></span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="section-title-row">
                <div>
                    <h2 class="section-heading mb-1">Workspace modules</h2>
                    <p class="text-muted mb-0">Open a module to review or manage its records.</p>
                </div>
            </div>

            <?php
            $modules = [
                ['Crops', 'Maintain crop varieties and growing details.', 'bi-flower2', 'crops/index.php'],
                ['Farmers', 'Manage farmer profiles and contact information.', 'bi-people', 'farmers/index.php'],
                ['Farms', 'Review farm location, area, soil, and irrigation.', 'bi-house', 'farms/index.php'],
                ['Planting Records', 'Track planting activities and their status.', 'bi-calendar2-check', 'planting/index.php'],
                ['Harvest Records', 'Record harvest quantities and quality.', 'bi-basket', 'harvest/index.php'],
                ['Reports', 'Review agricultural summaries and harvest value.', 'bi-file-earmark-bar-graph', 'reports/index.php'],
            ];
            ?>
            <div class="row g-3">
                <?php foreach ($modules as [$title, $description, $icon, $url]): ?>
                    <div class="col-md-6 col-xl-4">
                        <a class="quick-link" href="<?= BASE_URL ?>/<?= $url ?>">
                            <span class="quick-icon"><i class="bi <?= $icon ?>"></i></span>
                            <span>
                                <strong><?= htmlspecialchars($title) ?></strong>
                                <small><?= htmlspecialchars($description) ?></small>
                            </span>
                            <i class="bi bi-arrow-up-right quick-arrow"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($dashboard_error === ''): ?>
                <div class="row g-3 mt-2">
                    <div class="col-lg-6">
                        <section class="dashboard-card h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="section-heading mb-0">Recent plantings</h2>
                                <a href="<?= BASE_URL ?>/planting/index.php" class="small text-success">View all</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead><tr><th>Farm</th><th>Crop</th><th>Date</th><th>Status</th></tr></thead>
                                    <tbody>
                                        <?php if (!$recent_plantings): ?>
                                            <tr><td colspan="4" class="text-muted">No planting records yet.</td></tr>
                                        <?php endif; ?>
                                        <?php foreach ($recent_plantings as $row): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['farm_name']) ?></td>
                                                <td><?= htmlspecialchars($row['crop_name']) ?></td>
                                                <td><?= htmlspecialchars($row['planting_date']) ?></td>
                                                <td><?= htmlspecialchars($row['status_name']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    <div class="col-lg-6">
                        <section class="dashboard-card h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="section-heading mb-0">Recent harvests</h2>
                                <a href="<?= BASE_URL ?>/harvest/index.php" class="small text-success">View all</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead><tr><th>Farm</th><th>Crop</th><th>Date</th><th>Quantity</th></tr></thead>
                                    <tbody>
                                        <?php if (!$recent_harvests): ?>
                                            <tr><td colspan="4" class="text-muted">No harvest records yet.</td></tr>
                                        <?php endif; ?>
                                        <?php foreach ($recent_harvests as $row): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['farm_name']) ?></td>
                                                <td><?= htmlspecialchars($row['crop_name']) ?></td>
                                                <td><?= htmlspecialchars($row['harvest_date']) ?></td>
                                                <td><?= htmlspecialchars($row['quantity_kg']) ?> kg</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    <div class="col-lg-6">
                        <section class="dashboard-card">
                            <h2 class="section-heading">Plantings by status</h2>
                            <?php if (!$plantings_by_status): ?>
                                <p class="text-muted mb-0">No planting statuses are configured.</p>
                            <?php else: ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($plantings_by_status as $row): ?>
                                        <span class="badge bg-success-subtle text-success p-2">
                                            <?= htmlspecialchars($row['status_name']) ?>:
                                            <?= (int) $row['total'] ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </section>
                    </div>

                    <div class="col-lg-6">
                        <section class="dashboard-card">
                            <h2 class="section-heading">Total harvested quantity</h2>
                            <strong class="stat-value"><?= number_format($harvested_quantity, 2) ?> kg</strong>
                        </section>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

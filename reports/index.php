<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$page_title = 'Agricultural Reports';
$module_title = 'Agricultural Reports';
$module_description = 'Review summaries and records calculated from the database.';
$pdo = require __DIR__ . '/../config/database.php';

$type = is_string($_GET['type'] ?? null) ? $_GET['type'] : 'farmers';
$harvest_report_types = ['harvests', 'total_harvested_quantity', 'estimated_harvest_value'];
$allowed_reports = array_merge(['farmers', 'crops_by_farm', 'planting_status'], $harvest_report_types);
if (!in_array($type, $allowed_reports, true)) {
    $type = 'farmers';
}
$is_harvest_report = in_array($type, $harvest_report_types, true);

$read_filter = static function (string $name): string {
    return is_string($_GET[$name] ?? null) ? trim($_GET[$name]) : '';
};
$from = $read_filter('from');
$to = $read_filter('to');
$search = $read_filter('q');
$farmer_id = $read_filter('farmer_id');
$farm_id = $read_filter('farm_id');
$crop_id = $read_filter('crop_id');
$status_id = $read_filter('status_id');
$grade_id = $read_filter('grade_id');
$errors = [];
$invalid_date_range = false;

$farm_options = [];
$crop_options = [];
$farmer_options = [];
$status_options = [];
$grade_options = [];
if ($type === 'farmers') {
    $farmer_options = $pdo->query(
        'SELECT farmer_id, first_name, last_name FROM farmer ORDER BY last_name, first_name'
    )->fetchAll();
} elseif ($type === 'crops_by_farm') {
    $farm_options = $pdo->query('SELECT farm_id, farm_name FROM farm ORDER BY farm_name')->fetchAll();
    $crop_options = $pdo->query('SELECT crop_id, crop_name, variety FROM crop ORDER BY crop_name, variety')->fetchAll();
} elseif ($type === 'planting_status') {
    $status_options = $pdo->query('SELECT status_id, status_name FROM planting_status ORDER BY status_name')->fetchAll();
} else {
    $farm_options = $pdo->query('SELECT farm_id, farm_name FROM farm ORDER BY farm_name')->fetchAll();
    $grade_options = $pdo->query('SELECT grade_id, quality_grade FROM harvest_quality_grade ORDER BY quality_grade')->fetchAll();
}

$validate_selection = static function (string $value, array $options, string $idColumn) use (&$errors): string {
    if ($value === '') {
        return '';
    }
    $availableIds = array_map(static fn(array $option): int => (int) $option[$idColumn], $options);
    if (!ctype_digit($value) || !in_array((int) $value, $availableIds, true)) {
        $errors[] = 'Choose a valid value from the available report filters.';
        return '';
    }
    return (string) (int) $value;
};
$farmer_id = $type === 'farmers'
    ? $validate_selection($farmer_id, $farmer_options, 'farmer_id')
    : '';
$farm_id = in_array($type, array_merge(['crops_by_farm'], $harvest_report_types), true)
    ? $validate_selection($farm_id, $farm_options, 'farm_id')
    : '';
$crop_id = $type === 'crops_by_farm'
    ? $validate_selection($crop_id, $crop_options, 'crop_id')
    : '';
$status_id = $type === 'planting_status'
    ? $validate_selection($status_id, $status_options, 'status_id')
    : '';
$grade_id = $is_harvest_report
    ? $validate_selection($grade_id, $grade_options, 'grade_id')
    : '';

$is_valid_date = static function (string $value): bool {
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    $dateErrors = DateTime::getLastErrors();
    return $date !== false
        && $date->format('Y-m-d') === $value
        && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0));
};
if (($from !== '' && !$is_valid_date($from)) || ($to !== '' && !$is_valid_date($to))) {
    $errors[] = 'Enter valid dates for the selected report.';
    $from = '';
    $to = '';
} elseif ($from !== '' && $to !== '' && $from > $to) {
    $errors[] = 'The start date cannot be after the end date.';
    $invalid_date_range = true;
}
$error = implode(' ', array_unique($errors));

$rows = [];
$total_quantity = 0;
$estimated_value = 0;

if ($invalid_date_range) {
    // Keep the selected dates visible and skip the report query until corrected.
} elseif ($type === 'farmers') {
    $filters = [];
    $params = [];
    if ($search !== '') {
        $filters[] = '(farmer.first_name ILIKE :first_name
            OR farmer.last_name ILIKE :last_name
            OR farmer.contact_number ILIKE :contact
            OR farmer.address ILIKE :address)';
        $term = '%' . $search . '%';
        $params += [
            ':first_name' => $term,
            ':last_name' => $term,
            ':contact' => $term,
            ':address' => $term,
        ];
    }
    if ($farmer_id !== '') {
        $filters[] = 'farmer.farmer_id = :farmer_id';
        $params[':farmer_id'] = (int) $farmer_id;
    }
    if ($from !== '') {
        $filters[] = 'farmer.date_registered >= :date_from';
        $params[':date_from'] = $from;
    }
    if ($to !== '') {
        $filters[] = 'farmer.date_registered <= :date_to';
        $params[':date_to'] = $to;
    }
    $where = $filters ? ' WHERE ' . implode(' AND ', $filters) : '';
    $stmt = $pdo->prepare(
        'SELECT farmer.farmer_id, farmer.first_name, farmer.last_name,
                farmer.contact_number, farmer.address,
                COUNT(farm.farm_id) AS farm_count
         FROM farmer
         LEFT JOIN farm ON farm.farmer_id = farmer.farmer_id
         ' . $where . '
         GROUP BY farmer.farmer_id
         ORDER BY farmer.last_name, farmer.first_name'
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} elseif ($type === 'crops_by_farm') {
    $filters = [];
    $params = [];
    if ($farm_id !== '') {
        $filters[] = 'farm.farm_id = :farm_id';
        $params[':farm_id'] = (int) $farm_id;
    }
    if ($crop_id !== '') {
        $filters[] = 'crop.crop_id = :crop_id';
        $params[':crop_id'] = (int) $crop_id;
    }
    if ($from !== '') {
        $filters[] = 'planting.planting_date >= :date_from';
        $params[':date_from'] = $from;
    }
    if ($to !== '') {
        $filters[] = 'planting.planting_date <= :date_to';
        $params[':date_to'] = $to;
    }
    $where = $filters ? ' WHERE ' . implode(' AND ', $filters) : '';
    $stmt = $pdo->prepare(
        'SELECT farm.farm_id, farm.farm_name, crop.crop_name, crop.variety,
                COUNT(planting.planting_id) AS planting_count,
                COALESCE(SUM(planting.area_planted), 0) AS area_planted
         FROM farm
         JOIN planting ON planting.farm_id = farm.farm_id
         JOIN crop ON crop.crop_id = planting.crop_id
         ' . $where . '
         GROUP BY farm.farm_id, farm.farm_name,
                  crop.crop_id, crop.crop_name, crop.variety
         ORDER BY farm.farm_name, crop.crop_name'
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} elseif ($type === 'planting_status') {
    $filters = [];
    $params = [];
    if ($status_id !== '') {
        $filters[] = 'planting_status.status_id = :status_id';
        $params[':status_id'] = (int) $status_id;
    }
    if ($from !== '') {
        $filters[] = 'planting.planting_date >= :date_from';
        $params[':date_from'] = $from;
    }
    if ($to !== '') {
        $filters[] = 'planting.planting_date <= :date_to';
        $params[':date_to'] = $to;
    }
    $where = $filters ? ' WHERE ' . implode(' AND ', $filters) : '';
    $stmt = $pdo->prepare(
        'SELECT planting_status.status_name, planting.planting_id,
                planting.planting_date, planting.expected_harvest_date,
                farm.farm_name, crop.crop_name
         FROM planting_status
         LEFT JOIN planting ON planting.status_id = planting_status.status_id
         LEFT JOIN farm ON farm.farm_id = planting.farm_id
         LEFT JOIN crop ON crop.crop_id = planting.crop_id
         ' . $where . '
         ORDER BY planting_status.status_name, planting.planting_date DESC'
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} else {
    $filters = [];
    $params = [];
    if ($farm_id !== '') {
        $filters[] = 'farm.farm_id = :farm_id';
        $params[':farm_id'] = (int) $farm_id;
    }
    if ($grade_id !== '') {
        $filters[] = 'harvest.grade_id = :grade_id';
        $params[':grade_id'] = (int) $grade_id;
    }
    if ($from !== '') {
        $filters[] = 'harvest.harvest_date >= :date_from';
        $params[':date_from'] = $from;
    }
    if ($to !== '') {
        $filters[] = 'harvest.harvest_date <= :date_to';
        $params[':date_to'] = $to;
    }
    $where = $filters ? ' WHERE ' . implode(' AND ', $filters) : '';

    $sql = 'SELECT harvest.harvest_id, harvest.harvest_date,
                   harvest.quantity_kg, harvest.selling_price_per_kg,
                   harvest.quantity_kg * harvest.selling_price_per_kg AS estimated_value,
                   harvest_quality_grade.quality_grade,
                   harvest_remark.remark_description,
                   farm.farm_name, crop.crop_name
            FROM harvest
            JOIN planting ON planting.planting_id = harvest.planting_id
            JOIN farm ON farm.farm_id = planting.farm_id
            JOIN crop ON crop.crop_id = planting.crop_id
            JOIN harvest_quality_grade
                ON harvest_quality_grade.grade_id = harvest.grade_id
            JOIN harvest_remark
                ON harvest_remark.remark_id = harvest.remark_id'
        . $where
        . ' ORDER BY harvest.harvest_date DESC, harvest.harvest_id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $total_quantity += (float) $row['quantity_kg'];
        $estimated_value += (float) $row['estimated_value'];
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/module_top.php';
?>

<section class="dashboard-card mb-3">
    <form method="get" class="row g-3 align-items-end">
        <div class="col-lg-3">
            <label class="form-label" for="report-type">Report</label>
            <select id="report-type" name="type" class="form-select">
                <option value="farmers" <?= $type === 'farmers' ? 'selected' : '' ?>>
                    Farmers and their farms
                </option>
                <option value="crops_by_farm" <?= $type === 'crops_by_farm' ? 'selected' : '' ?>>
                    Crops planted per farm
                </option>
                <option value="planting_status" <?= $type === 'planting_status' ? 'selected' : '' ?>>
                    Planting records by status
                </option>
                <option value="harvests" <?= $type === 'harvests' ? 'selected' : '' ?>>
                    Harvest records and value
                </option>
                <option value="total_harvested_quantity" <?= $type === 'total_harvested_quantity' ? 'selected' : '' ?>>
                    Total harvested quantity
                </option>
                <option value="estimated_harvest_value" <?= $type === 'estimated_harvest_value' ? 'selected' : '' ?>>
                    Estimated harvest value
                </option>
            </select>
        </div>

        <?php if ($type === 'farmers'): ?>
            <div class="col-md-3">
                <label class="form-label" for="farmer-search">Search farmers</label>
                <input id="farmer-search" name="q" class="form-control"
                       placeholder="Name, contact, or address" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="farmer-filter">Farmer</label>
                <select id="farmer-filter" name="farmer_id" class="form-select">
                    <option value="">All farmers</option>
                    <?php foreach ($farmer_options as $farmer): ?>
                        <option value="<?= (int) $farmer['farmer_id'] ?>"
                            <?= $farmer_id === (string) $farmer['farmer_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($farmer['last_name'] . ', ' . $farmer['first_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php elseif ($type === 'crops_by_farm'): ?>
            <div class="col-md-3">
                <label class="form-label" for="farm-filter">Farm</label>
                <select id="farm-filter" name="farm_id" class="form-select">
                    <option value="">All farms</option>
                    <?php foreach ($farm_options as $farm): ?>
                        <option value="<?= (int) $farm['farm_id'] ?>"
                            <?= $farm_id === (string) $farm['farm_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($farm['farm_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="crop-filter">Crop</label>
                <select id="crop-filter" name="crop_id" class="form-select">
                    <option value="">All crops</option>
                    <?php foreach ($crop_options as $crop): ?>
                        <option value="<?= (int) $crop['crop_id'] ?>"
                            <?= $crop_id === (string) $crop['crop_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($crop['crop_name'] . ($crop['variety'] ? ' · ' . $crop['variety'] : '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php elseif ($type === 'planting_status'): ?>
            <div class="col-md-3">
                <label class="form-label" for="status-filter">Planting status</label>
                <select id="status-filter" name="status_id" class="form-select">
                    <option value="">All statuses</option>
                    <?php foreach ($status_options as $status): ?>
                        <option value="<?= (int) $status['status_id'] ?>"
                            <?= $status_id === (string) $status['status_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($status['status_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php else: ?>
            <div class="col-md-3">
                <label class="form-label" for="harvest-farm-filter">Farm</label>
                <select id="harvest-farm-filter" name="farm_id" class="form-select">
                    <option value="">All farms</option>
                    <?php foreach ($farm_options as $farm): ?>
                        <option value="<?= (int) $farm['farm_id'] ?>"
                            <?= $farm_id === (string) $farm['farm_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($farm['farm_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="grade-filter">Quality grade</label>
                <select id="grade-filter" name="grade_id" class="form-select">
                    <option value="">All grades</option>
                    <?php foreach ($grade_options as $grade): ?>
                        <option value="<?= (int) $grade['grade_id'] ?>"
                            <?= $grade_id === (string) $grade['grade_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($grade['quality_grade']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="col-md-2">
            <label class="form-label" for="date-from"><?= $type === 'farmers' ? 'Registered' : ($is_harvest_report ? 'Harvest' : 'Planting') ?> date from</label>
            <input id="date-from" type="date" name="from" class="form-control"
                   value="<?= htmlspecialchars($from) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="date-to"><?= $type === 'farmers' ? 'Registered' : ($is_harvest_report ? 'Harvest' : 'Planting') ?> date to</label>
            <input id="date-to" type="date" name="to" class="form-control"
                   value="<?= htmlspecialchars($to) ?>">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-success">Run report</button>
            <a class="btn btn-outline-secondary" href="index.php?type=<?= urlencode($type) ?>">Clear</a>
        </div>
    </form>
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger mt-3 mb-0"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
</section>

<?php if ($type === 'farmers'): ?>
    <section class="dashboard-card">
        <h2 class="section-heading">Farmers and their farms</h2>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Farmer</th><th>Contact</th><th>Address</th><th>Registered farms</th></tr></thead>
                <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="4" class="text-muted">No farmer records found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                            <td><?= htmlspecialchars($row['contact_number'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['address']) ?></td>
                            <td><?= (int) $row['farm_count'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php elseif ($type === 'crops_by_farm'): ?>
    <section class="dashboard-card">
        <h2 class="section-heading">Crops planted per farm</h2>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Farm</th><th>Crop</th><th>Variety</th><th>Planting records</th><th>Area planted (ha)</th></tr></thead>
                <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="5" class="text-muted">No crop plantings found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['farm_name']) ?></td>
                            <td><?= htmlspecialchars($row['crop_name']) ?></td>
                            <td><?= htmlspecialchars($row['variety']) ?></td>
                            <td><?= (int) $row['planting_count'] ?></td>
                            <td><?= htmlspecialchars($row['area_planted']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php elseif ($type === 'planting_status'): ?>
    <section class="dashboard-card">
        <h2 class="section-heading">Planting records by status</h2>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Status</th><th>Planting ID</th><th>Farm</th><th>Crop</th><th>Planting date</th><th>Expected harvest</th></tr></thead>
                <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="6" class="text-muted">No planting statuses found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['status_name']) ?></td>
                            <td><?= htmlspecialchars($row['planting_id'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($row['farm_name'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($row['crop_name'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($row['planting_date'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($row['expected_harvest_date'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php else: ?>
    <section class="mb-3" aria-labelledby="harvest-summary-title">
        <h2 id="harvest-summary-title" class="section-heading mb-1">
            <?= $type === 'total_harvested_quantity' ? 'Total harvested quantity report' : ($type === 'estimated_harvest_value' ? 'Estimated harvest value report' : 'Harvest summary') ?>
        </h2>
        <p class="text-muted mb-3">
            Based on <?= count($rows) ?> matching harvest record<?= count($rows) === 1 ? '' : 's' ?>.
        </p>
    <div class="row g-3 mb-3">
        <?php if (in_array($type, ['harvests', 'total_harvested_quantity'], true)): ?>
        <div class="col-md-6">
            <div class="dashboard-card">
                <span class="text-muted">Total harvested quantity</span>
                <strong class="stat-value d-block"><?= number_format($total_quantity, 2) ?> kg</strong>
                <small class="text-muted">Sum of harvested quantities</small>
            </div>
        </div>
        <?php endif; ?>
        <?php if (in_array($type, ['harvests', 'estimated_harvest_value'], true)): ?>
        <div class="col-md-6">
            <div class="dashboard-card">
                <span class="text-muted">Estimated harvest value</span>
                <strong class="stat-value d-block"><?= number_format($estimated_value, 2) ?></strong>
                <small class="text-muted">Sum of quantity multiplied by selling price per kg</small>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($type === 'harvests'): ?>
    <section class="dashboard-card">
        <h2 class="section-heading">Harvest report</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th><th>Farm</th><th>Crop</th><th>Quantity (kg)</th>
                        <th>Price / kg</th><th>Grade</th><th>Remark</th><th>Estimated value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="8" class="text-muted">No harvest records found for this date range.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['harvest_date']) ?></td>
                            <td><?= htmlspecialchars($row['farm_name']) ?></td>
                            <td><?= htmlspecialchars($row['crop_name']) ?></td>
                            <td><?= htmlspecialchars($row['quantity_kg']) ?></td>
                            <td><?= number_format((float) $row['selling_price_per_kg'], 2) ?></td>
                            <td><?= htmlspecialchars($row['quality_grade']) ?></td>
                            <td><?= htmlspecialchars($row['remark_description']) ?></td>
                            <td><?= number_format((float) $row['estimated_value'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>
    </section>
<?php endif; ?>

<?php include __DIR__ . '/../includes/module_bottom.php'; ?>

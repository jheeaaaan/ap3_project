<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/listing_tools.php';
$page_title = 'Planting Records';
$module_title = 'Planting Records';
$module_description = 'Schedule and monitor planting activities.';
$module_action_url = 'create.php';
$module_action_label = 'Add Planting';
$pdo = require __DIR__ . '/../config/database.php';

$search = trim($_GET['q'] ?? '');
$status_id = $_GET['status_id'] ?? '';
$sort_labels = [
    'id' => 'Planting ID',
    'farm' => 'Farm',
    'crop' => 'Crop',
    'planting_date' => 'Planting date',
    'expected_harvest_date' => 'Expected harvest',
    'area' => 'Area planted',
    'status' => 'Status',
];
$sort_columns = [
    'id' => 'planting.planting_id',
    'farm' => 'farm.farm_name',
    'crop' => 'crop.crop_name',
    'planting_date' => 'planting.planting_date',
    'expected_harvest_date' => 'planting.expected_harvest_date',
    'area' => 'planting.area_planted',
    'status' => 'planting_status.status_name',
];
[$sort, $direction, $order_by] = listing_sort_state($sort_columns, 'planting_date');
$page = max(1, (int) ($_GET['page'] ?? 1));
$per_page = 10;
$statuses = $pdo->query(
    'SELECT status_id, status_name FROM planting_status ORDER BY status_name'
)->fetchAll();

$filters = [];
$params = [];
if ($search !== '') {
    $filters[] = '(farm.farm_name ILIKE :farm_search
        OR crop.crop_name ILIKE :crop_search
        OR farmer.first_name ILIKE :first_search
        OR farmer.last_name ILIKE :last_search)';
    $term = '%' . $search . '%';
    $params = [
        ':farm_search' => $term,
        ':crop_search' => $term,
        ':first_search' => $term,
        ':last_search' => $term,
    ];
}
if ($status_id !== '' && ctype_digit((string) $status_id)) {
    $filters[] = 'planting.status_id = :status_id';
    $params[':status_id'] = (int) $status_id;
}

$where = $filters ? ' WHERE ' . implode(' AND ', $filters) : '';
$joins = ' FROM planting
    JOIN farm ON farm.farm_id = planting.farm_id
    JOIN crop ON crop.crop_id = planting.crop_id
    JOIN farmer ON farmer.farmer_id = farm.farmer_id';
$count = $pdo->prepare('SELECT COUNT(*)' . $joins . $where);
$count->execute($params);
$total = (int) $count->fetchColumn();
$pages = max(1, (int) ceil($total / $per_page));
$page = min($page, $pages);

$sql = 'SELECT planting.planting_id, planting.planting_date,
               planting.expected_harvest_date, planting.area_planted,
               planting.seed_quantity, farm.farm_name, crop.crop_name,
               farmer.first_name, farmer.last_name, planting_status.status_name
        FROM planting
        JOIN farm ON farm.farm_id = planting.farm_id
        JOIN farmer ON farmer.farmer_id = farm.farmer_id
        JOIN crop ON crop.crop_id = planting.crop_id
        JOIN planting_status ON planting_status.status_id = planting.status_id'
    . $where
    . ' ORDER BY ' . $order_by . ', planting.planting_id DESC
        LIMIT :limit OFFSET :offset';
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', ($page - 1) * $per_page, PDO::PARAM_INT);
$stmt->execute();
$records = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/module_top.php';
?>

<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
 if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        Planting record <?= htmlspecialchars($_GET['success']) ?> successfully.
    </div>
<?php endif; ?>

<section class="dashboard-card">
    <form method="get" class="row g-2 align-items-end mb-3">
        <div class="col-md-3">
            <label class="form-label" for="q">Search farm, crop, or farmer</label>
            <input id="q" class="form-control" name="q" value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="status_id">Planting status</label>
            <select id="status_id" class="form-select" name="status_id">
                <option value="">All statuses</option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= (int) $status['status_id'] ?>"
                        <?= (string) $status_id === (string) $status['status_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($status['status_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="planting-sort">Sort by</label>
            <select id="planting-sort" name="sort" class="form-select">
                <?php foreach ($sort_labels as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= $sort === $key ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="planting-direction">Order</label>
            <select id="planting-direction" name="direction" class="form-select">
                <option value="desc" <?= $direction === 'desc' ? 'selected' : '' ?>>Descending</option>
                <option value="asc" <?= $direction === 'asc' ? 'selected' : '' ?>>Ascending</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-success w-100">Filter</button>
            <a class="btn btn-outline-secondary" href="index.php">Clear</a>
        </div>
    </form>

    <div class="d-flex justify-content-between mb-3">
        <h2 class="section-heading mb-0">Planting register</h2>
        <span class="badge bg-success-subtle text-success"><?= $total ?> records</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>ID</th><th>Farm / Farmer</th><th>Crop</th>
                    <th>Planting date</th><th>Expected harvest</th>
                    <th>Area (ha)</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$records): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">
                        No planting records match your search.
                    </td></tr>
                <?php endif; ?>
                <?php foreach ($records as $row): ?>
                    <tr>
                        <td><?= (int) $row['planting_id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['farm_name']) ?></strong>
                            <small class="d-block text-muted">
                                <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
                            </small>
                        </td>
                        <td><?= htmlspecialchars($row['crop_name']) ?></td>
                        <td><?= htmlspecialchars($row['planting_date']) ?></td>
                        <td><?= htmlspecialchars($row['expected_harvest_date']) ?></td>
                        <td><?= htmlspecialchars($row['area_planted']) ?></td>
                        <td><span class="badge bg-success-subtle text-success">
                            <?= htmlspecialchars($row['status_name']) ?>
                        </span></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a class="btn btn-sm btn-outline-success"
                                   href="read.php?id=<?= (int) $row['planting_id'] ?>">View</a>
                                <a class="btn btn-sm btn-outline-primary"
                                   href="update.php?id=<?= (int) $row['planting_id'] ?>">Edit</a>
                                <?php if (($_SESSION['auth_role'] ?? '') === 'Administrator'): ?>
                                    <a class="btn btn-sm btn-outline-danger"
                                       href="delete.php?id=<?= (int) $row['planting_id'] ?>">Delete</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
        <nav aria-label="Planting record pages">
            <ul class="pagination mb-0">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query([
                            'q' => $search,
                            'status_id' => $status_id,
                            'sort' => $sort,
                            'direction' => $direction,
                            'page' => $i,
                        ]) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/../includes/module_bottom.php'; ?>

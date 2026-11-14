<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/listing_tools.php';
$page_title = 'Harvest Records';
$module_title = 'Harvest Records';
$module_description = 'Review harvest dates, quantities, grades, and estimated value.';
$module_action_url = 'create.php';
$module_action_label = 'Add Harvest';
$pdo = require __DIR__ . '/../config/database.php';

$search = trim($_GET['q'] ?? '');
$grade_id = $_GET['grade_id'] ?? '';
$sort_labels = [
    'id' => 'Harvest ID',
    'farm' => 'Farm',
    'crop' => 'Crop',
    'harvest_date' => 'Harvest date',
    'quantity' => 'Quantity',
    'grade' => 'Quality grade',
    'estimated_value' => 'Estimated value',
];
$sort_columns = [
    'id' => 'harvest.harvest_id',
    'farm' => 'farm.farm_name',
    'crop' => 'crop.crop_name',
    'harvest_date' => 'harvest.harvest_date',
    'quantity' => 'harvest.quantity_kg',
    'grade' => 'harvest_quality_grade.quality_grade',
    'estimated_value' => '(harvest.quantity_kg * harvest.selling_price_per_kg)',
];
[$sort, $direction, $order_by] = listing_sort_state($sort_columns, 'harvest_date');
$page = max(1, (int) ($_GET['page'] ?? 1));
$per_page = 10;
$grades = $pdo->query(
    'SELECT grade_id, quality_grade
     FROM harvest_quality_grade
     ORDER BY quality_grade'
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
if ($grade_id !== '' && ctype_digit((string) $grade_id)) {
    $filters[] = 'harvest.grade_id = :grade_id';
    $params[':grade_id'] = (int) $grade_id;
}
$where = $filters ? ' WHERE ' . implode(' AND ', $filters) : '';
$joins = ' FROM harvest
    JOIN planting ON planting.planting_id = harvest.planting_id
    JOIN farm ON farm.farm_id = planting.farm_id
    JOIN farmer ON farmer.farmer_id = farm.farmer_id
    JOIN crop ON crop.crop_id = planting.crop_id';

$count = $pdo->prepare('SELECT COUNT(*)' . $joins . $where);
$count->execute($params);
$total = (int) $count->fetchColumn();
$pages = max(1, (int) ceil($total / $per_page));
$page = min($page, $pages);

$sql = 'SELECT harvest.harvest_id, harvest.harvest_date,
               harvest.quantity_kg, harvest.selling_price_per_kg,
               harvest_quality_grade.quality_grade, farm.farm_name,
               crop.crop_name, farmer.first_name, farmer.last_name'
    . $joins
    . ' JOIN harvest_quality_grade
        ON harvest_quality_grade.grade_id = harvest.grade_id'
    . $where
    . ' ORDER BY ' . $order_by . ', harvest.harvest_id DESC
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
        Harvest record <?= htmlspecialchars($_GET['success']) ?> successfully.
    </div>
<?php endif; ?>

<section class="dashboard-card">
    <form method="get" class="row g-2 align-items-end mb-3">
        <div class="col-md-3">
            <label class="form-label" for="search">Search farm, crop, or farmer</label>
            <input id="search" name="q" class="form-control" value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="grade">Quality grade</label>
            <select id="grade" name="grade_id" class="form-select">
                <option value="">All grades</option>
                <?php foreach ($grades as $grade): ?>
                    <option value="<?= (int) $grade['grade_id'] ?>"
                        <?= (string) $grade_id === (string) $grade['grade_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($grade['quality_grade']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="harvest-sort">Sort by</label>
            <select id="harvest-sort" name="sort" class="form-select">
                <?php foreach ($sort_labels as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= $sort === $key ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="harvest-direction">Order</label>
            <select id="harvest-direction" name="direction" class="form-select">
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
        <h2 class="section-heading mb-0">Harvest register</h2>
        <span class="badge bg-success-subtle text-success"><?= $total ?> records</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>ID</th><th>Farm / Farmer</th><th>Crop</th><th>Harvest date</th>
                    <th>Quantity (kg)</th><th>Grade</th><th>Estimated value</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$records): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">
                        No harvest records match your search.
                    </td></tr>
                <?php endif; ?>
                <?php foreach ($records as $record): ?>
                    <tr>
                        <td><?= (int) $record['harvest_id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($record['farm_name']) ?></strong>
                            <small class="d-block text-muted">
                                <?= htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) ?>
                            </small>
                        </td>
                        <td><?= htmlspecialchars($record['crop_name']) ?></td>
                        <td><?= htmlspecialchars($record['harvest_date']) ?></td>
                        <td><?= htmlspecialchars($record['quantity_kg']) ?></td>
                        <td><?= htmlspecialchars($record['quality_grade']) ?></td>
                        <td><?= number_format(
                            (float) $record['quantity_kg'] * (float) $record['selling_price_per_kg'],
                            2
                        ) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a class="btn btn-sm btn-outline-success"
                                   href="read.php?id=<?= (int) $record['harvest_id'] ?>">View</a>
                                <a class="btn btn-sm btn-outline-primary"
                                   href="update.php?id=<?= (int) $record['harvest_id'] ?>">Edit</a>
                                <?php if (($_SESSION['auth_role'] ?? '') === 'Administrator'): ?>
                                    <a class="btn btn-sm btn-outline-danger"
                                       href="delete.php?id=<?= (int) $record['harvest_id'] ?>">Delete</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
        <nav aria-label="Harvest record pages">
            <ul class="pagination mb-0">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query([
                            'q' => $search,
                            'grade_id' => $grade_id,
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


<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/listing_tools.php';


// Connect to the database
$pdo = require __DIR__ . '/../config/database.php';

$search = trim($_GET['q'] ?? '');
$irrigation_filter = $_GET['irrigation_type_id'] ?? '';
$sort_labels = [
    'id' => 'Farm ID',
    'farm_name' => 'Farm name',
    'farmer' => 'Farmer',
    'location' => 'Location',
    'area' => 'Area',
    'soil' => 'Soil type',
    'irrigation' => 'Irrigation type',
];
$sort_columns = [
    'id' => 'farm.farm_id',
    'farm_name' => 'farm.farm_name',
    'farmer' => "CONCAT(farmer.last_name, ', ', farmer.first_name)",
    'location' => 'farm.location',
    'area' => 'farm.area_hectares',
    'soil' => 'soil_type.soil_type_name',
    'irrigation' => 'irrigation_type.irrigation_description',
];
[$sort, $direction, $order_by] = listing_sort_state($sort_columns, 'id');
$page = max(1, (int) ($_GET['page'] ?? 1));
$per_page = 10;
$filters = [];
$params = [];
if ($search !== '') {
    $filters[] = '(farm.farm_name ILIKE :farm_name
        OR farm.location ILIKE :location
        OR farmer.first_name ILIKE :first_name
        OR farmer.last_name ILIKE :last_name)';
    $term = '%' . $search . '%';
    $params += [
        ':farm_name' => $term,
        ':location' => $term,
        ':first_name' => $term,
        ':last_name' => $term,
    ];
}
if ($irrigation_filter !== '' && ctype_digit((string) $irrigation_filter)) {
    $filters[] = 'farm.irrigation_type_id = :irrigation';
    $params[':irrigation'] = (int) $irrigation_filter;
}
$where = $filters ? ' WHERE ' . implode(' AND ', $filters) : '';
$joins = ' FROM farm
    JOIN farmer ON farm.farmer_id = farmer.farmer_id
    JOIN soil_type ON farm.soil_type_id = soil_type.soil_type_id
    JOIN irrigation_type ON farm.irrigation_type_id = irrigation_type.irrigation_type_id';
$count = $pdo->prepare('SELECT COUNT(*)' . $joins . $where);
$count->execute($params);
$total_farms = (int) $count->fetchColumn();
$pages = max(1, (int) ceil($total_farms / $per_page));
$page = min($page, $pages);

$sql = 'SELECT farm.farm_id, farm.farm_name, farm.location, farm.area_hectares,
               farmer.first_name, farmer.last_name, soil_type.soil_type_name,
               irrigation_type.irrigation_description'
    . $joins . $where
    . ' ORDER BY ' . $order_by . ', farm.farm_id DESC LIMIT :limit OFFSET :offset';
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', ($page - 1) * $per_page, PDO::PARAM_INT);
$stmt->execute();
$farms = $stmt->fetchAll();
$irrigation_types = $pdo->query(
    'SELECT irrigation_type_id, irrigation_description
     FROM irrigation_type ORDER BY irrigation_description'
)->fetchAll();

// Include website header
include __DIR__ . '/../includes/header.php';

?>

<div class="app-shell">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <div class="topbar">
            <div>
                <h4>Farm Management</h4>
                <p>Manage and monitor registered farms.</p>
            </div>

            <a href="create.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Add Farm
            </a>
        </div>

        <?php if (isset($_GET['added'])) { ?>
            <div class="alert alert-success">
                Farm successfully added!
            </div>
        <?php } ?>

        <?php if (isset($_GET['updated'])) { ?>
            <div class="alert alert-success">
                Farm information successfully updated!
            </div>
        <?php } ?>

        <?php if (isset($_GET['deleted'])) { ?>
            <div class="alert alert-success">
                Farm successfully deleted!
            </div>
        <?php } ?>

        <div class="dashboard-card">

            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="farm-search">Search farm, location, or farmer</label>
                    <input id="farm-search" class="form-control" name="q"
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="irrigation-type">Irrigation type</label>
                    <select id="irrigation-type" class="form-select" name="irrigation_type_id">
                        <option value="">All types</option>
                        <?php foreach ($irrigation_types as $type): ?>
                            <option value="<?= (int) $type['irrigation_type_id'] ?>"
                                <?= (string) $irrigation_filter === (string) $type['irrigation_type_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['irrigation_description']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="farm-sort">Sort by</label>
                    <select id="farm-sort" name="sort" class="form-select">
                        <?php foreach ($sort_labels as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>" <?= $sort === $key ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="farm-direction">Order</label>
                    <select id="farm-direction" name="direction" class="form-select">
                        <option value="desc" <?= $direction === 'desc' ? 'selected' : '' ?>>Descending</option>
                        <option value="asc" <?= $direction === 'asc' ? 'selected' : '' ?>>Ascending</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button class="btn btn-success">Filter</button>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <a href="index.php" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
            <div class="d-flex justify-content-between mb-3">
                <h5 class="fw-bold">Registered Farms</h5>

                <span class="badge bg-success-subtle text-success">
                    Total: <?= $total_farms ?>
                </span>
            </div>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Farm Name</th>
                            <th>Farmer</th>
                            <th>Location</th>
                            <th>Area (ha)</th>
                            <th>Soil Type</th>
                            <th>Irrigation</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if ($total_farms > 0) { ?>

                            <?php foreach ($farms as $farm) { ?>

                                <tr>
                                    <td><?= htmlspecialchars($farm['farm_id']) ?></td>

                                    <td class="fw-semibold">
                                        <?= htmlspecialchars($farm['farm_name']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($farm['first_name'] . ' ' . $farm['last_name']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($farm['location']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($farm['area_hectares']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($farm['soil_type_name']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($farm['irrigation_description']) ?>
                                    </td>

                                    <td>
                                        <div class="d-flex gap-1">

                                            <a href="read.php?id=<?= $farm['farm_id'] ?>"
                                               class="btn btn-sm btn-outline-success">
                                                Read
                                            </a>

                                            <a href="update.php?id=<?= $farm['farm_id'] ?>"
                                               class="btn btn-sm btn-outline-primary">
                                                Update
                                            </a>

                                            <?php if (($_SESSION['auth_role'] ?? '') === 'Administrator'): ?>
                                                <a href="delete.php?id=<?= $farm['farm_id'] ?>"
                                                   class="btn btn-sm btn-outline-danger">Delete</a>
                                            <?php endif; ?>

                                        </div>
                                    </td>
                                </tr>

                            <?php } ?>

                        <?php } else { ?>

                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    No farm records found.
                                </td>
                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>
            <?php if ($pages > 1): ?>
                <nav aria-label="Farm pages">
                    <ul class="pagination mb-0">
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query([
                                    'q' => $search,
                                    'irrigation_type_id' => $irrigation_filter,
                                    'sort' => $sort,
                                    'direction' => $direction,
                                    'page' => $i,
                                ]) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>

        </div>

    </main>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

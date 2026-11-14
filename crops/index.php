
<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/listing_tools.php';


// Connect to the database
$pdo = require __DIR__ . '/../config/database.php';

$search = trim($_GET['q'] ?? '');
$category_filter = $_GET['crop_category_id'] ?? '';
$sort_labels = [
    'id' => 'Crop ID',
    'crop_name' => 'Crop name',
    'variety' => 'Variety',
    'category' => 'Category',
    'growing_period' => 'Growing period',
    'season' => 'Season',
];
$sort_columns = [
    'id' => 'crop.crop_id',
    'crop_name' => 'crop.crop_name',
    'variety' => 'crop.variety',
    'category' => 'crop_category.category_name',
    'growing_period' => 'crop.growing_period_days',
    'season' => 'season.season_name',
];
[$sort, $direction, $order_by] = listing_sort_state($sort_columns, 'id');
$page = max(1, (int) ($_GET['page'] ?? 1));
$per_page = 10;
$filters = [];
$params = [];

if ($search !== '') {
    $filters[] = '(crop.crop_name ILIKE :name OR crop.variety ILIKE :variety)';
    $term = '%' . $search . '%';
    $params[':name'] = $term;
    $params[':variety'] = $term;
}
if ($category_filter !== '' && ctype_digit((string) $category_filter)) {
    $filters[] = 'crop.crop_category_id = :category';
    $params[':category'] = (int) $category_filter;
}

$where = $filters ? ' WHERE ' . implode(' AND ', $filters) : '';
$joins = ' FROM crop
    JOIN crop_category ON crop.crop_category_id = crop_category.crop_category_id
    JOIN season ON crop.season_id = season.season_id';
$count = $pdo->prepare('SELECT COUNT(*)' . $joins . $where);
$count->execute($params);
$total_crops = (int) $count->fetchColumn();
$pages = max(1, (int) ceil($total_crops / $per_page));
$page = min($page, $pages);

$sql = 'SELECT crop.crop_id, crop.crop_name, crop.variety,
               crop.growing_period_days, crop_category.category_name,
               season.season_name'
    . $joins . $where
    . ' ORDER BY ' . $order_by . ', crop.crop_id DESC LIMIT :limit OFFSET :offset';
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', ($page - 1) * $per_page, PDO::PARAM_INT);
$stmt->execute();
$crops = $stmt->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query(
    'SELECT crop_category_id, category_name
     FROM crop_category ORDER BY category_name'
)->fetchAll();

// Include website header
include __DIR__ . '/../includes/header.php';

?>

<div class="app-shell">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <div class="topbar">
            <div>
                <h4>Crop Management</h4>
                <p>Manage registered crops and their information.</p>
            </div>

            <a href="create.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Add Crop
            </a>
        </div>

        <?php if (isset($_GET['success'])) { ?>
            <div class="alert alert-success">
                Crop successfully <?php echo htmlspecialchars($_GET['success']); ?>!
            </div>
        <?php } ?>

        <div class="dashboard-card">

            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="crop-search">Search crop or variety</label>
                    <input id="crop-search" class="form-control" name="q"
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="crop-category">Crop category</label>
                    <select id="crop-category" class="form-select" name="crop_category_id">
                        <option value="">All categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['crop_category_id'] ?>"
                                <?= (string) $category_filter === (string) $category['crop_category_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="crop-sort">Sort by</label>
                    <select id="crop-sort" name="sort" class="form-select">
                        <?php foreach ($sort_labels as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>" <?= $sort === $key ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="crop-direction">Order</label>
                    <select id="crop-direction" name="direction" class="form-select">
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
                <h5 class="fw-bold">Crop Records</h5>

                <span class="badge bg-success-subtle text-success">
                    Total: <?php echo $total_crops; ?>
                </span>
            </div>
            <?php if ($pages > 1): ?>
                <nav aria-label="Crop pages">
                    <ul class="pagination mb-0">
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query([
                                    'q' => $search,
                                    'crop_category_id' => $category_filter,
                                    'sort' => $sort,
                                    'direction' => $direction,
                                    'page' => $i,
                                ]) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Crop Name</th>
                            <th>Variety</th>
                            <th>Category</th>
                            <th>Growing Period</th>
                            <th>Season</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if ($total_crops > 0) { ?>

                            <?php foreach ($crops as $crop) { ?>

                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($crop['crop_id']); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($crop['crop_name']); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($crop['variety'] ?? ''); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($crop['category_name']); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($crop['growing_period_days']); ?> days
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($crop['season_name']); ?>
                                    </td>

                                    <td>
                                        <div class="d-flex gap-1">

                                            <a href="read.php?id=<?php echo $crop['crop_id']; ?>"
                                               class="btn btn-sm btn-outline-success">
                                                Read
                                            </a>

                                            <a href="update.php?id=<?php echo $crop['crop_id']; ?>"
                                               class="btn btn-sm btn-outline-primary">
                                                Update
                                            </a>

                                            <?php if (($_SESSION['auth_role'] ?? '') === 'Administrator'): ?>
                                                <a href="delete.php?id=<?php echo $crop['crop_id']; ?>"
                                                   class="btn btn-sm btn-outline-danger">Delete</a>
                                            <?php endif; ?>

                                        </div>
                                    </td>
                                </tr>

                            <?php } ?>

                        <?php } else { ?>

                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No crop records found.
                                </td>
                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

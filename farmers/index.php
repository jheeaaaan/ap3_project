
<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/listing_tools.php';


// Connect to the database
$pdo = require __DIR__ . '/../config/database.php';

// Search, address filter, sorting, and pagination share one validated query state.
$search = trim($_GET['q'] ?? '');
$address_filter = trim($_GET['address'] ?? '');
$sort_labels = [
    'id' => 'Farmer ID',
    'first_name' => 'First name',
    'last_name' => 'Last name',
    'address' => 'Address',
    'date_registered' => 'Date registered',
];
$sort_columns = [
    'id' => 'farmer_id',
    'first_name' => 'first_name',
    'last_name' => 'last_name',
    'address' => 'address',
    'date_registered' => 'date_registered',
];
[$sort, $direction, $order_by] = listing_sort_state($sort_columns, 'id');
$page = max(1, (int) ($_GET['page'] ?? 1));
$per_page = 10;
$filters = [];
$params = [];
if ($search !== '') {
    $filters[] = '(first_name ILIKE :first_name
        OR last_name ILIKE :last_name
        OR contact_number ILIKE :contact
        OR address ILIKE :search_address)';
    $term = '%' . $search . '%';
    $params = [
        ':first_name' => $term,
        ':last_name' => $term,
        ':contact' => $term,
        ':search_address' => $term,
    ];
}
$where = '';
if ($address_filter !== '') {
    $filters[] = 'address ILIKE :address_filter';
    $params[':address_filter'] = '%' . $address_filter . '%';
}
if ($filters) {
    $where = ' WHERE ' . implode(' AND ', $filters);
}
$count = $pdo->prepare('SELECT COUNT(*) FROM farmer' . $where);
$count->execute($params);
$total_farmers = (int) $count->fetchColumn();
$pages = max(1, (int) ceil($total_farmers / $per_page));
$page = min($page, $pages);
$stmt = $pdo->prepare('SELECT * FROM farmer' . $where . ' ORDER BY ' . $order_by . ', farmer_id DESC LIMIT :limit OFFSET :offset');
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', ($page - 1) * $per_page, PDO::PARAM_INT);
$stmt->execute();
$farmers = $stmt->fetchAll();

// Include website header
include __DIR__ . '/../includes/header.php';

?>

<div class="app-shell">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <div class="topbar">
            <div>
                <h4>Farmer Management</h4>
                <p>Manage registered farmer information.</p>
            </div>

            <a href="create.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Add Farmer
            </a>
        </div>

        <?php if (isset($_GET['added']) || (($_GET['message'] ?? '') === 'added')) { ?>
            <div class="alert alert-success">
                Farmer successfully added!
            </div>
        <?php } ?>

        <?php if (isset($_GET['updated'])) { ?>
            <div class="alert alert-success">
                Farmer information successfully updated!
            </div>
        <?php } ?>

        <?php if (isset($_GET['deleted']) || (($_GET['message'] ?? '') === 'deleted')) { ?>
            <div class="alert alert-success">
                Farmer successfully deleted!
            </div>
        <?php } ?>

        <div class="dashboard-card">

            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="farmer-search">Search name, contact, or address</label>
                    <input id="farmer-search" name="q" class="form-control"
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="farmer-address">Address contains</label>
                    <input id="farmer-address" name="address" class="form-control"
                           value="<?= htmlspecialchars($address_filter) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="farmer-sort">Sort by</label>
                    <select id="farmer-sort" name="sort" class="form-select">
                        <?php foreach ($sort_labels as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>" <?= $sort === $key ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="farmer-direction">Order</label>
                    <select id="farmer-direction" name="direction" class="form-select">
                        <option value="desc" <?= $direction === 'desc' ? 'selected' : '' ?>>Descending</option>
                        <option value="asc" <?= $direction === 'asc' ? 'selected' : '' ?>>Ascending</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button class="btn btn-success">Search</button>
                    <a href="index.php" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
            <div class="d-flex justify-content-between mb-3">
                <h5 class="fw-bold">Farmer Records</h5>

                <span class="badge bg-success-subtle text-success">
                    Total: <?= $total_farmers ?>
                </span>
            </div>

            <?php if ($pages > 1): ?>
                <nav aria-label="Farmer pages">
                    <ul class="pagination mb-0">
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query([
                                    'q' => $search,
                                    'address' => $address_filter,
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
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Contact Number</th>
                            <th>Address</th>
                            <th>Date Registered</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if ($total_farmers > 0) { ?>

                            <?php foreach ($farmers as $farmer) { ?>

                                <tr>
                                    <td><?= htmlspecialchars($farmer['farmer_id']) ?></td>

                                    <td><?= htmlspecialchars($farmer['first_name']) ?></td>

                                    <td><?= htmlspecialchars($farmer['last_name']) ?></td>

                                    <td><?= htmlspecialchars($farmer['contact_number'] ?? '') ?></td>

                                    <td><?= htmlspecialchars($farmer['address']) ?></td>

                                    <td><?= htmlspecialchars($farmer['date_registered']) ?></td>

                                    <td>
                                        <div class="d-flex gap-1">

                                            <a href="read.php?id=<?= $farmer['farmer_id'] ?>"
                                               class="btn btn-sm btn-outline-success">
                                                Read
                                            </a>

                                            <a href="update.php?id=<?= $farmer['farmer_id'] ?>"
                                               class="btn btn-sm btn-outline-primary">
                                                Update
                                            </a>

                                            <?php if (($_SESSION['auth_role'] ?? '') === 'Administrator'): ?>
                                                <a href="delete.php?id=<?= $farmer['farmer_id'] ?>"
                                                   class="btn btn-sm btn-outline-danger">Delete</a>
                                            <?php endif; ?>

                                        </div>
                                    </td>
                                </tr>

                            <?php } ?>

                        <?php } else { ?>

                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No farmer records found.
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

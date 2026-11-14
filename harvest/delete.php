<?php
require_once __DIR__ . '/../includes/auth.php';
require_administrator();
auth_require_valid_csrf();
$page_title = 'Delete Harvest';
$module_title = 'Delete Harvest Record';
$module_description = 'Confirm before permanently removing this harvest.';
$pdo = require __DIR__ . '/../config/database.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

$stmt = $pdo->prepare('SELECT harvest_id FROM harvest WHERE harvest_id = :id');
$stmt->execute([':id' => $id]);
$exists = $stmt->fetchColumn();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $exists) {
    $stmt = $pdo->prepare('DELETE FROM harvest WHERE harvest_id = :id');
    $stmt->execute([':id' => $id]);
    header('Location: index.php?success=deleted');
    exit;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/module_top.php';
?>

<div class="dashboard-card">
    <?php
require_once __DIR__ . '/../includes/auth.php';
require_administrator();
 if (!$exists): ?>
        <div class="alert alert-warning">Harvest record not found.</div>
    <?php else: ?>
        <p>Delete harvest record #<?= (int) $id ?> permanently?</p>
        <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(auth_csrf_token()) ?>">
            <button class="btn btn-danger">Yes, delete record</button>
            <a class="btn btn-outline-secondary" href="index.php">Cancel</a>
        </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/module_bottom.php'; ?>

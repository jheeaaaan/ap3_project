<?php
require_once __DIR__ . '/../includes/auth.php';
require_administrator();
auth_require_valid_csrf();
$page_title = 'Delete Planting Record';
$module_title = 'Delete Planting Record';
$module_description = 'Confirm before permanently removing this record.';
$pdo = require __DIR__ . '/../config/database.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

$stmt = $pdo->prepare('SELECT planting_id FROM planting WHERE planting_id = :id');
$stmt->execute([':id' => $id]);
$exists = $stmt->fetchColumn();
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $exists) {
    try {
        $stmt = $pdo->prepare('DELETE FROM planting WHERE planting_id = :id');
        $stmt->execute([':id' => $id]);
        header('Location: index.php?success=deleted');
        exit;
    } catch (PDOException $e) {
        $error = 'This planting record cannot be deleted while harvest records refer to it.';
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/module_top.php';
?>

<div class="dashboard-card">
    <?php
require_once __DIR__ . '/../includes/auth.php';
require_administrator();
 if (!$exists): ?>
        <div class="alert alert-warning">Planting record not found.</div>
    <?php else: ?>
        <p>Delete planting record #<?= (int) $id ?> permanently?</p>
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(auth_csrf_token()) ?>">
            <button class="btn btn-danger">Yes, delete record</button>
            <a class="btn btn-outline-secondary" href="index.php">Cancel</a>
        </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/module_bottom.php'; ?>

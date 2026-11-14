
<?php
require_once __DIR__ . '/../includes/auth.php';
require_administrator();
auth_require_valid_csrf();

$pdo = require __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? '';
$error = '';

$stmt = $pdo->prepare("SELECT * FROM crop WHERE crop_id = :id");
$stmt->execute([':id' => $id]);
$crop = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $crop) {
    try {
        $stmt = $pdo->prepare("DELETE FROM crop WHERE crop_id = :id");
        $stmt->execute([':id' => $id]);

        header('Location: index.php?success=deleted');
        exit;

    } catch (PDOException $e) {
        $error = 'This crop cannot be deleted because it may already be used in planting records.';
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-content">
<div class="container-fluid py-4">
    <h2 class="fw-bold text-danger">Delete Crop</h2>

    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body p-4">

            <?php if ($crop): ?>

                <p>Are you sure you want to delete this crop?</p>

                <p><strong>Crop Name:</strong> <?php echo htmlspecialchars($crop['crop_name']); ?></p>
                <p><strong>Variety:</strong> <?php echo htmlspecialchars($crop['variety']); ?></p>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(auth_csrf_token()) ?>">
                    <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </form>

            <?php else: ?>

                <div class="alert alert-warning">Crop record not found.</div>
                <a href="index.php" class="btn btn-secondary">Back</a>

            <?php endif; ?>

        </div>
    </div>
</div>

        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

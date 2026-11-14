
<?php
require_once __DIR__ . '/../includes/auth.php';
require_administrator();
auth_require_valid_csrf();


// Connect to the database
$pdo = require __DIR__ . '/../config/database.php';

// Get the farm ID
$farm_id = $_GET['id'] ?? '';

// Find the farm
$stmt = $pdo->prepare("SELECT farm_id, farm_name FROM farm WHERE farm_id = :farm_id");
$stmt->execute(['farm_id' => $farm_id]);

$farm = $stmt->fetch();

// Return to the farm list if the farm does not exist
if (!$farm) {
    header("Location: index.php");
    exit;
}

// Check if the delete button was clicked
if (isset($_POST['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM farm WHERE farm_id = :farm_id");
        $stmt->execute(['farm_id' => $farm_id]);
        header("Location: index.php?deleted=1");
        exit;
    } catch (PDOException $e) {
        $error = 'This farm cannot be deleted while planting records are linked to it.';
    }
}

// Include website header
include __DIR__ . '/../includes/header.php';

?>

<div class="app-shell">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <div class="topbar">
            <div>
                <h4>Delete Farm</h4>
                <p>Remove a farm record from the system.</p>
            </div>

            <a href="index.php" class="btn btn-outline-secondary">
                Back to Farms
            </a>
        </div>

        <div class="dashboard-card">

            <?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div class="text-center py-4">

                <i class="bi bi-exclamation-triangle text-danger"
                   style="font-size: 45px;"></i>

                <h5 class="fw-bold mt-3">Are you sure?</h5>

                <p class="text-muted">
                    You are about to delete the following farm:
                </p>

                <h5 class="text-danger">
                    <?= htmlspecialchars($farm['farm_name']) ?>
                </h5>

                <p class="text-muted">
                    This action cannot be undone.
                </p>

                <form method="POST" class="d-flex justify-content-center gap-2 mt-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(auth_csrf_token()) ?>">

                    <button type="submit"
                            name="delete"
                            class="btn btn-danger">
                        Yes, Delete Farm
                    </button>

                    <a href="index.php" class="btn btn-light">
                        Cancel
                    </a>

                </form>

            </div>

        </div>

    </main>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

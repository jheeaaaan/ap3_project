
<?php
require_once __DIR__ . '/../includes/auth.php';
require_administrator();
auth_require_valid_csrf();


// Connect to the database
$pdo = require __DIR__ . '/../config/database.php';

// Get the farmer ID
$id = $_GET['id'] ?? '';

// Find the farmer
$sql = "SELECT * FROM farmer WHERE farmer_id = :id";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id]);

$farmer = $stmt->fetch();

// Check if the farmer exists
if (!$farmer) {
    header("Location: index.php");
    exit;
}

$error = '';
// Check if the delete button was clicked
if (isset($_POST['delete'])) {
    try {
        $sql = "DELETE FROM farmer WHERE farmer_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        header("Location: index.php?message=deleted");
        exit;
    } catch (PDOException $e) {
        $error = 'This farmer cannot be deleted while farm records are linked to them.';
    }
}

// Include the website header
include __DIR__ . '/../includes/header.php';

?>

<div class="app-shell">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <div class="topbar">
            <div>
                <h4>Delete Farmer</h4>
                <p>Review the farmer's information before deleting.</p>
            </div>

            <a href="index.php" class="btn btn-outline-secondary">
                Back to Farmers
            </a>
        </div>

        <div class="dashboard-card">

            <h5 class="fw-bold mb-3">Confirm Deletion</h5>

            <?php if ($error !== ''): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div class="alert alert-warning">
                Are you sure you want to delete this farmer's record?
                This action cannot be undone.
            </div>

            <p>
                <strong>Farmer Name:</strong>
                <?= htmlspecialchars($farmer['first_name'] . ' ' . $farmer['last_name']) ?>
            </p>

            <p>
                <strong>Farmer ID:</strong>
                <?= htmlspecialchars($farmer['farmer_id']) ?>
            </p>

            <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(auth_csrf_token()) ?>">

                <button type="submit"
                        name="delete"
                        class="btn btn-danger">
                    Yes, Delete Farmer
                </button>

                <a href="index.php" class="btn btn-outline-secondary">
                    Cancel
                </a>

            </form>

        </div>

    </main>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

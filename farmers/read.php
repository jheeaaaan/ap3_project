
<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();


// Connect to the database
$pdo = require __DIR__ . '/../config/database.php';

// Get the farmer ID from the URL
$farmer_id = $_GET['id'] ?? '';

// Find the selected farmer
$sql = "SELECT * FROM farmer WHERE farmer_id = :farmer_id";

$stmt = $pdo->prepare($sql);
$stmt->execute(['farmer_id' => $farmer_id]);

$farmer = $stmt->fetch();

// Return to the farmer list if the record does not exist
if (!$farmer) {
    header("Location: index.php");
    exit;
}

// Include website header
include __DIR__ . '/../includes/header.php';

?>

<div class="app-shell">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <div class="topbar">
            <div>
                <h4>Farmer Details</h4>
                <p>View the complete farmer information.</p>
            </div>

            <a href="index.php" class="btn btn-outline-secondary">
                Back to Farmers
            </a>
        </div>

        <div class="dashboard-card">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h5 class="fw-bold mb-0">
                    <?= htmlspecialchars($farmer['first_name'] . ' ' . $farmer['last_name']) ?>
                </h5>

                <span class="badge bg-success-subtle text-success">
                    Farmer ID: <?= htmlspecialchars($farmer['farmer_id']) ?>
                </span>

            </div>

            <div class="row">

                <div class="col-md-6 mb-3">
                    <label class="text-muted">First Name</label>
                    <p class="fw-semibold">
                        <?= htmlspecialchars($farmer['first_name']) ?>
                    </p>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="text-muted">Last Name</label>
                    <p class="fw-semibold">
                        <?= htmlspecialchars($farmer['last_name']) ?>
                    </p>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="text-muted">Contact Number</label>
                    <p class="fw-semibold">
                        <?= htmlspecialchars($farmer['contact_number'] ?? 'Not provided') ?>
                    </p>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="text-muted">Date Registered</label>
                    <p class="fw-semibold">
                        <?= htmlspecialchars($farmer['date_registered']) ?>
                    </p>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="text-muted">Address</label>
                    <p class="fw-semibold">
                        <?= htmlspecialchars($farmer['address']) ?>
                    </p>
                </div>

            </div>

            <div class="d-flex gap-2 mt-3">

                <a href="update.php?id=<?= $farmer['farmer_id'] ?>"
                   class="btn btn-primary">
                    Update Farmer
                </a>

                <?php if (($_SESSION['auth_role'] ?? '') === 'Administrator'): ?>
                    <a href="delete.php?id=<?= $farmer['farmer_id'] ?>" class="btn btn-outline-danger">Delete Farmer</a>
                <?php endif; ?>

            </div>

        </div>

    </main>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

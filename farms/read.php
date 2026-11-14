
<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();


// Connect to the database
$pdo = require __DIR__ . '/../config/database.php';

// Get the farm ID from the URL
$farm_id = $_GET['id'] ?? '';

// Find the selected farm
$sql = "SELECT
            farm.farm_id,
            farm.farm_name,
            farm.location,
            farm.area_hectares,
            farmer.first_name,
            farmer.last_name,
            farmer.contact_number,
            soil_type.soil_type_name,
            irrigation_type.irrigation_description
        FROM farm
        INNER JOIN farmer
            ON farm.farmer_id = farmer.farmer_id
        INNER JOIN soil_type
            ON farm.soil_type_id = soil_type.soil_type_id
        INNER JOIN irrigation_type
            ON farm.irrigation_type_id = irrigation_type.irrigation_type_id
        WHERE farm.farm_id = :farm_id";

$stmt = $pdo->prepare($sql);
$stmt->execute(['farm_id' => $farm_id]);

$farm = $stmt->fetch();

// Return to the farm list if the record does not exist
if (!$farm) {
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
                <h4>Farm Details</h4>
                <p>View the complete farm information.</p>
            </div>

            <a href="index.php" class="btn btn-outline-secondary">
                Back to Farms
            </a>
        </div>

        <div class="dashboard-card">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h5 class="fw-bold mb-0">
                    <?= htmlspecialchars($farm['farm_name']) ?>
                </h5>

                <span class="badge bg-success-subtle text-success">
                    Farm ID: <?= htmlspecialchars($farm['farm_id']) ?>
                </span>

            </div>

            <div class="row">

                <div class="col-md-6 mb-3">
                    <label class="text-muted">Farm Name</label>
                    <p class="fw-semibold">
                        <?= htmlspecialchars($farm['farm_name']) ?>
                    </p>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="text-muted">Registered Farmer</label>
                    <p class="fw-semibold">
                        <?= htmlspecialchars($farm['first_name'] . ' ' . $farm['last_name']) ?>
                    </p>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="text-muted">Farmer Contact Number</label>
                    <p class="fw-semibold">
                        <?= htmlspecialchars($farm['contact_number'] ?? 'Not provided') ?>
                    </p>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="text-muted">Farm Area</label>
                    <p class="fw-semibold">
                        <?= htmlspecialchars($farm['area_hectares']) ?> hectares
                    </p>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="text-muted">Farm Location</label>
                    <p class="fw-semibold">
                        <?= htmlspecialchars($farm['location']) ?>
                    </p>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="text-muted">Soil Type</label>
                    <p class="fw-semibold">
                        <?= htmlspecialchars($farm['soil_type_name']) ?>
                    </p>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="text-muted">Irrigation Type</label>
                    <p class="fw-semibold">
                        <?= htmlspecialchars($farm['irrigation_description']) ?>
                    </p>
                </div>

            </div>

            <div class="d-flex gap-2 mt-3">

                <a href="update.php?id=<?= $farm['farm_id'] ?>"
                   class="btn btn-primary">
                    Update Farm
                </a>

                <?php if (($_SESSION['auth_role'] ?? '') === 'Administrator'): ?>
                    <a href="delete.php?id=<?= $farm['farm_id'] ?>" class="btn btn-outline-danger">Delete Farm</a>
                <?php endif; ?>

            </div>

        </div>

    </main>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

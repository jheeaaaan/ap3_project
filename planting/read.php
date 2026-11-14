<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$page_title = 'Planting Details';
$module_title = 'Planting Details';
$module_description = 'Review related farm, crop, and planting information.';
$pdo = require __DIR__ . '/../config/database.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

$stmt = $pdo->prepare(
    'SELECT planting.*, farm.farm_name, farm.location,
            crop.crop_name, crop.variety,
            farmer.first_name, farmer.last_name,
            planting_status.status_name
     FROM planting
     JOIN farm ON farm.farm_id = planting.farm_id
     JOIN farmer ON farmer.farmer_id = farm.farmer_id
     JOIN crop ON crop.crop_id = planting.crop_id
     JOIN planting_status ON planting_status.status_id = planting.status_id
     WHERE planting.planting_id = :id'
);
$stmt->execute([':id' => $id]);
$record = $stmt->fetch();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/module_top.php';
?>

<div class="dashboard-card">
    <?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
 if (!$record): ?>
        <div class="alert alert-warning">Planting record not found.</div>
    <?php else: ?>
        <h2 class="section-heading">Record #<?= (int) $record['planting_id'] ?></h2>
        <div class="row g-3">
            <div class="col-md-6">
                <strong>Farm</strong>
                <p><?= htmlspecialchars($record['farm_name'] . ' - ' . $record['location']) ?></p>
            </div>
            <div class="col-md-6">
                <strong>Farmer</strong>
                <p><?= htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) ?></p>
            </div>
            <div class="col-md-6">
                <strong>Crop</strong>
                <p><?= htmlspecialchars($record['crop_name'] . ' - ' . $record['variety']) ?></p>
            </div>
            <div class="col-md-6">
                <strong>Status</strong>
                <p><?= htmlspecialchars($record['status_name']) ?></p>
            </div>
            <div class="col-md-6">
                <strong>Planting date</strong>
                <p><?= htmlspecialchars($record['planting_date']) ?></p>
            </div>
            <div class="col-md-6">
                <strong>Expected harvest</strong>
                <p><?= htmlspecialchars($record['expected_harvest_date']) ?></p>
            </div>
            <div class="col-md-6">
                <strong>Area planted</strong>
                <p><?= htmlspecialchars($record['area_planted']) ?> ha</p>
            </div>
            <div class="col-md-6">
                <strong>Seed quantity</strong>
                <p><?= htmlspecialchars($record['seed_quantity']) ?></p>
            </div>
        </div>
        <a class="btn btn-primary" href="update.php?id=<?= (int) $record['planting_id'] ?>">Edit</a>
    <?php endif; ?>
    <a class="btn btn-outline-secondary" href="index.php">Back</a>
</div>

<?php include __DIR__ . '/../includes/module_bottom.php'; ?>

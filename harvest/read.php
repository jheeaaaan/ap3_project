<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$page_title = 'Harvest Details';
$module_title = 'Harvest Details';
$module_description = 'Review harvest and linked planting details.';
$pdo = require __DIR__ . '/../config/database.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

$stmt = $pdo->prepare(
    'SELECT harvest.*, planting.planting_date, farm.farm_name,
            crop.crop_name, farmer.first_name, farmer.last_name,
            harvest_quality_grade.quality_grade,
            harvest_remark.remark_description
     FROM harvest
     JOIN planting ON planting.planting_id = harvest.planting_id
     JOIN farm ON farm.farm_id = planting.farm_id
     JOIN farmer ON farmer.farmer_id = farm.farmer_id
     JOIN crop ON crop.crop_id = planting.crop_id
     JOIN harvest_quality_grade
        ON harvest_quality_grade.grade_id = harvest.grade_id
     JOIN harvest_remark
        ON harvest_remark.remark_id = harvest.remark_id
     WHERE harvest.harvest_id = :id'
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
        <div class="alert alert-warning">Harvest record not found.</div>
    <?php else: ?>
        <h2 class="section-heading">Record #<?= (int) $record['harvest_id'] ?></h2>
        <div class="row g-3">
            <div class="col-md-6">
                <strong>Farm</strong>
                <p><?= htmlspecialchars($record['farm_name']) ?></p>
            </div>
            <div class="col-md-6">
                <strong>Farmer</strong>
                <p><?= htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) ?></p>
            </div>
            <div class="col-md-6">
                <strong>Crop</strong>
                <p><?= htmlspecialchars($record['crop_name']) ?></p>
            </div>
            <div class="col-md-6">
                <strong>Planting date</strong>
                <p><?= htmlspecialchars($record['planting_date']) ?></p>
            </div>
            <div class="col-md-6">
                <strong>Harvest date</strong>
                <p><?= htmlspecialchars($record['harvest_date']) ?></p>
            </div>
            <div class="col-md-6">
                <strong>Quantity</strong>
                <p><?= htmlspecialchars($record['quantity_kg']) ?> kg</p>
            </div>
            <div class="col-md-6">
                <strong>Selling price per kg</strong>
                <p><?= number_format((float) $record['selling_price_per_kg'], 2) ?></p>
            </div>
            <div class="col-md-6">
                <strong>Estimated value</strong>
                <p><?= number_format(
                    (float) $record['quantity_kg'] * (float) $record['selling_price_per_kg'],
                    2
                ) ?></p>
            </div>
            <div class="col-md-6">
                <strong>Quality grade</strong>
                <p><?= htmlspecialchars($record['quality_grade']) ?></p>
            </div>
            <div class="col-md-6">
                <strong>Remark</strong>
                <p><?= htmlspecialchars($record['remark_description']) ?></p>
            </div>
        </div>
        <a class="btn btn-primary" href="update.php?id=<?= (int) $record['harvest_id'] ?>">Edit</a>
    <?php endif; ?>
    <a class="btn btn-outline-secondary" href="index.php">Back</a>
</div>

<?php include __DIR__ . '/../includes/module_bottom.php'; ?>

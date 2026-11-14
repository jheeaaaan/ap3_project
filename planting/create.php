<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
auth_require_valid_csrf();
$page_title = 'Add Planting Record';
$module_title = 'Add Planting Record';
$module_description = 'Enter a planting and link it to the correct farm, crop, and status.';
$pdo = require __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/validation.php';

$farms = $pdo->query(
    'SELECT farm_id, farm_name FROM farm ORDER BY farm_name'
)->fetchAll();
$crops = $pdo->query(
    'SELECT crop_id, crop_name, variety FROM crop ORDER BY crop_name'
)->fetchAll();
$statuses = $pdo->query(
    'SELECT status_id, status_name FROM planting_status ORDER BY status_name'
)->fetchAll();

$values = [
    'farm_id' => '',
    'crop_id' => '',
    'planting_date' => '',
    'expected_harvest_date' => '',
    'area_planted' => '',
    'seed_quantity' => '',
    'status_id' => '',
];
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    foreach ($values as $key => $_) {
        $values[$key] = trim($_POST[$key] ?? '');
    }

    if (in_array('', $values, true)) {
        $error = 'Complete all required fields.';
    } elseif (
        !lookup_option_exists($farms, $values['farm_id'], 'farm_id')
        || !lookup_option_exists($crops, $values['crop_id'], 'crop_id')
        || !lookup_option_exists($statuses, $values['status_id'], 'status_id')
    ) {
        $error = 'Choose an existing farm, crop, and status from the available options.';
    } elseif (
        !is_numeric($values['area_planted'])
        || (float) $values['area_planted'] <= 0
        || !is_numeric($values['seed_quantity'])
        || (float) $values['seed_quantity'] < 0
    ) {
        $error = 'Area must be greater than zero and seed quantity cannot be negative.';
    } elseif (
        !is_valid_iso_date($values['planting_date'])
        || !is_valid_iso_date($values['expected_harvest_date'])
        || $values['expected_harvest_date'] < $values['planting_date']
    ) {
        $error = 'Enter valid dates; expected harvest must be on or after planting.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO planting
                    (farm_id, crop_id, planting_date, area_planted,
                     seed_quantity, expected_harvest_date, status_id,
                     created_by, updated_by)
                 VALUES
                    (:farm, :crop, :plant_date, :area,
                     :seed, :expected, :status, :created_by, :updated_by)'
            );
            $stmt->execute([
                ':farm' => (int) $values['farm_id'],
                ':crop' => (int) $values['crop_id'],
                ':plant_date' => $values['planting_date'],
                ':area' => $values['area_planted'],
                ':seed' => $values['seed_quantity'],
                ':expected' => $values['expected_harvest_date'],
                ':status' => (int) $values['status_id'],
                ':created_by' => $_SESSION['auth_user'],
                ':updated_by' => $_SESSION['auth_user'],
            ]);
            header('Location: index.php?success=created');
            exit;
        } catch (PDOException $e) {
            $error = 'Could not save the record. Check that the selected farm, crop, and status are still available.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/module_top.php';
?>

<div class="form-panel dashboard-card">
    <h2 class="section-heading">Planting information</h2>
    <?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
 if ($error !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$farms || !$crops || !$statuses): ?>
        <div class="alert alert-info">
            Add at least one farm, crop, and planting status before creating a planting record.
        </div>
    <?php else: ?>
        <form method="post" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(auth_csrf_token()) ?>">
            <div class="col-md-6">
                <label class="form-label">Farm *</label>
                <select class="form-select" name="farm_id" required>
                    <option value="">Choose a farm</option>
                    <?php foreach ($farms as $farm): ?>
                        <option value="<?= (int) $farm['farm_id'] ?>"
                            <?= $values['farm_id'] == $farm['farm_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($farm['farm_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Crop *</label>
                <select class="form-select" name="crop_id" required>
                    <option value="">Choose a crop</option>
                    <?php foreach ($crops as $crop): ?>
                        <option value="<?= (int) $crop['crop_id'] ?>"
                            <?= $values['crop_id'] == $crop['crop_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($crop['crop_name'] . ' - ' . $crop['variety']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Planting date *</label>
                <input type="date" class="form-control" name="planting_date"
                       value="<?= htmlspecialchars($values['planting_date']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Expected harvest date *</label>
                <input type="date" class="form-control" name="expected_harvest_date"
                       value="<?= htmlspecialchars($values['expected_harvest_date']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Area planted (ha) *</label>
                <input type="number" step="0.01" min="0.01" class="form-control"
                       name="area_planted" value="<?= htmlspecialchars($values['area_planted']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Seed quantity *</label>
                <input type="number" step="0.01" min="0" class="form-control"
                       name="seed_quantity" value="<?= htmlspecialchars($values['seed_quantity']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Status *</label>
                <select class="form-select" name="status_id" required>
                    <option value="">Choose status</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?= (int) $status['status_id'] ?>"
                            <?= $values['status_id'] == $status['status_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($status['status_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <button class="btn btn-success">Save Planting</button>
                <a class="btn btn-outline-secondary" href="index.php">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/module_bottom.php'; ?>

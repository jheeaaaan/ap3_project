<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
auth_require_valid_csrf();
require_once __DIR__ . '/../includes/validation.php';
$page_title = 'Edit Harvest';
$module_title = 'Edit Harvest Record';
$module_description = 'Update harvest quantity, date, grade, or remark.';
$pdo = require __DIR__ . '/../config/database.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

$stmt = $pdo->prepare('SELECT * FROM harvest WHERE harvest_id = :id');
$stmt->execute([':id' => $id]);
$record = $stmt->fetch();
if (!$record) {
    header('Location: index.php?error=notfound');
    exit;
}

$plantings = $pdo->query(
    'SELECT planting.planting_id, planting.planting_date,
            farm.farm_name, crop.crop_name
     FROM planting
     JOIN farm ON farm.farm_id = planting.farm_id
     JOIN crop ON crop.crop_id = planting.crop_id
     ORDER BY planting.planting_date DESC'
)->fetchAll();
$grades = $pdo->query(
    'SELECT grade_id, quality_grade
     FROM harvest_quality_grade
     ORDER BY quality_grade'
)->fetchAll();
$remarks = $pdo->query(
    'SELECT remark_id, remark_description
     FROM harvest_remark
     ORDER BY remark_description'
)->fetchAll();

$values = [
    'planting_id' => $record['planting_id'],
    'harvest_date' => $record['harvest_date'],
    'quantity_kg' => $record['quantity_kg'],
    'selling_price_per_kg' => $record['selling_price_per_kg'],
    'grade_id' => $record['grade_id'],
    'remark_id' => $record['remark_id'],
];
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    foreach ($values as $key => $_) {
        $values[$key] = trim($_POST[$key] ?? '');
    }

    if (in_array('', $values, true)) {
        $error = 'Complete all required fields.';
    } elseif (
        !lookup_option_exists($plantings, $values['planting_id'], 'planting_id')
        || !lookup_option_exists($grades, $values['grade_id'], 'grade_id')
        || !lookup_option_exists($remarks, $values['remark_id'], 'remark_id')
    ) {
        $error = 'Choose an existing planting, quality grade, and remark from the available options.';
    } elseif (
        !is_numeric($values['quantity_kg'])
        || (float) $values['quantity_kg'] <= 0
        || !is_numeric($values['selling_price_per_kg'])
        || (float) $values['selling_price_per_kg'] < 0
    ) {
        $error = 'Quantity must be greater than zero and selling price cannot be negative.';
    } elseif (!is_valid_iso_date($values['harvest_date'])) {
        $error = 'Enter a valid harvest date.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT planting_date FROM planting WHERE planting_id = :id'
        );
        $stmt->execute([':id' => (int) $values['planting_id']]);
        $planting_date = $stmt->fetchColumn();

        if (!$planting_date) {
            $error = 'Select an existing planting record.';
        } elseif ($values['harvest_date'] < $planting_date) {
            $error = 'Harvest date cannot be earlier than its planting date.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    'UPDATE harvest
                     SET planting_id = :planting,
                         harvest_date = :date,
                         quantity_kg = :quantity,
                         selling_price_per_kg = :price,
                         grade_id = :grade,
                         remark_id = :remark,
                         updated_by = :updated_by
                     WHERE harvest_id = :id'
                );
                $stmt->execute([
                    ':planting' => (int) $values['planting_id'],
                    ':date' => $values['harvest_date'],
                    ':quantity' => $values['quantity_kg'],
                    ':price' => $values['selling_price_per_kg'],
                    ':grade' => (int) $values['grade_id'],
                    ':remark' => (int) $values['remark_id'],
                    ':updated_by' => $_SESSION['auth_user'],
                    ':id' => $id,
                ]);
                header('Location: index.php?success=updated');
                exit;
            } catch (PDOException $e) {
                $error = 'Could not update the harvest record. Check the selected related values.';
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/module_top.php';
?>

<div class="form-panel dashboard-card">
    <h2 class="section-heading">Harvest information</h2>
    <?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
 if ($error !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$plantings || !$grades || !$remarks): ?>
        <div class="alert alert-info">
            The planting, quality grade, and harvest remark options are required to edit this record.
        </div>
    <?php else: ?>
    <form method="post" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(auth_csrf_token()) ?>">
        <div class="col-md-8">
            <label class="form-label">Planting record *</label>
            <select name="planting_id" class="form-select" required>
                <?php foreach ($plantings as $planting): ?>
                    <option value="<?= (int) $planting['planting_id'] ?>"
                        <?= $values['planting_id'] == $planting['planting_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(
                            '#' . $planting['planting_id'] . ' - '
                            . $planting['farm_name'] . ' / '
                            . $planting['crop_name'] . ' ('
                            . $planting['planting_date'] . ')'
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Harvest date *</label>
            <input type="date" name="harvest_date" class="form-control"
                   value="<?= htmlspecialchars($values['harvest_date']) ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Quantity (kg) *</label>
            <input type="number" step="0.01" min="0.01" name="quantity_kg"
                   class="form-control" value="<?= htmlspecialchars($values['quantity_kg']) ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Selling price per kg *</label>
            <input type="number" step="0.01" min="0" name="selling_price_per_kg"
                   class="form-control" value="<?= htmlspecialchars($values['selling_price_per_kg']) ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Quality grade *</label>
            <select name="grade_id" class="form-select" required>
                <?php foreach ($grades as $grade): ?>
                    <option value="<?= (int) $grade['grade_id'] ?>"
                        <?= $values['grade_id'] == $grade['grade_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($grade['quality_grade']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-8">
            <label class="form-label">Harvest remark *</label>
            <select name="remark_id" class="form-select" required>
                <?php foreach ($remarks as $remark): ?>
                    <option value="<?= (int) $remark['remark_id'] ?>"
                        <?= $values['remark_id'] == $remark['remark_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($remark['remark_description']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <button class="btn btn-success">Update Harvest</button>
            <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/module_bottom.php'; ?>

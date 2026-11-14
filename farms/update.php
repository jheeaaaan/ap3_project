
<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
auth_require_valid_csrf();


// Connect to the database
$pdo = require __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/validation.php';

// Get the farm ID
$farm_id = $_GET['id'] ?? '';

// Find the existing farm
$stmt = $pdo->prepare(
    "SELECT * FROM farm WHERE farm_id = :farm_id"
);

$stmt->execute(['farm_id' => $farm_id]);

$farm = $stmt->fetch();

// Return to the farm list if the farm does not exist
if (!$farm) {
    header("Location: index.php");
    exit;
}

$error = "";

// Get the existing farm information
$farmer_id = $farm['farmer_id'];
$farm_name = $farm['farm_name'];
$location = $farm['location'];
$area_hectares = $farm['area_hectares'];
$soil_type_id = $farm['soil_type_id'];
$irrigation_type_id = $farm['irrigation_type_id'];

// Get farmers for the dropdown
$farmers = $pdo->query(
    "SELECT farmer_id, first_name, last_name
     FROM farmer
     ORDER BY first_name"
)->fetchAll();

// Get soil types for the dropdown
$soil_types = $pdo->query(
    "SELECT soil_type_id, soil_type_name
     FROM soil_type
     ORDER BY soil_type_name"
)->fetchAll();

// Get irrigation types for the dropdown
$irrigation_types = $pdo->query(
    "SELECT irrigation_type_id, irrigation_description
     FROM irrigation_type
     ORDER BY irrigation_description"
)->fetchAll();

// Check if the form was submitted
if (isset($_POST['submit'])) {

    // Get the updated form values
    $farmer_id = $_POST['farmer_id'] ?? '';
    $farm_name = trim($_POST['farm_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $area_hectares = trim($_POST['area_hectares'] ?? '');
    $soil_type_id = $_POST['soil_type_id'] ?? '';
    $irrigation_type_id = $_POST['irrigation_type_id'] ?? '';

    // Check required fields
    if (
        empty($farmer_id) ||
        empty($farm_name) ||
        empty($location) ||
        empty($area_hectares) ||
        empty($soil_type_id) ||
        empty($irrigation_type_id)
    ) {

        $error = "Please complete all fields.";

    } elseif (
        !lookup_option_exists($farmers, $farmer_id, 'farmer_id')
        || !lookup_option_exists($soil_types, $soil_type_id, 'soil_type_id')
        || !lookup_option_exists($irrigation_types, $irrigation_type_id, 'irrigation_type_id')
    ) {
        $error = 'Choose a valid farmer, soil type, and irrigation type from the available options.';
    } elseif (!is_numeric($area_hectares) || $area_hectares <= 0) {

        $error = "Please enter a valid farm area.";

    } else {

        // Update the farm information
        $sql = "UPDATE farm SET
                    farmer_id = :farmer_id,
                    farm_name = :farm_name,
                    location = :location,
                    area_hectares = :area_hectares,
                    soil_type_id = :soil_type_id,
                    irrigation_type_id = :irrigation_type_id,
                    updated_by = :updated_by
                WHERE farm_id = :farm_id";

        try {
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            'farmer_id' => $farmer_id,
            'farm_name' => $farm_name,
            'location' => $location,
            'area_hectares' => $area_hectares,
            'soil_type_id' => $soil_type_id,
            'irrigation_type_id' => $irrigation_type_id,
            'updated_by' => $_SESSION['auth_user'],
            'farm_id' => $farm_id
        ]);

        // Return to the farm list
        header("Location: index.php?updated=1");
        exit;

        } catch (PDOException $e) {
            $error = "Unable to update the farm. Check the selected farmer and lookup values.";
        }

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
                <h4>Update Farm</h4>
                <p>Update the registered farm information.</p>
            </div>

            <a href="index.php" class="btn btn-outline-secondary">
                Back to Farms
            </a>
        </div>

        <div class="dashboard-card">

            <h5 class="fw-bold mb-4">Update Farm Information</h5>

            <?php if ($error != "") { ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php } ?>

            <?php if (!$farmers || !$soil_types || !$irrigation_types): ?>
                <div class="alert alert-info">
                    The farmer and lookup options are required to edit this farm.
                </div>
            <?php else: ?>
            <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(auth_csrf_token()) ?>">

                <div class="mb-3">
                    <label class="form-label">Farmer</label>

                    <select name="farmer_id" class="form-select" required>

                        <option value="">Select Farmer</option>

                        <?php foreach ($farmers as $farmer) { ?>

                            <option value="<?= $farmer['farmer_id'] ?>"
                                <?= ($farmer_id == $farmer['farmer_id']) ? 'selected' : '' ?>>

                                <?= htmlspecialchars($farmer['first_name'] . ' ' . $farmer['last_name']) ?>

                            </option>

                        <?php } ?>

                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Farm Name</label>

                    <input type="text"
                           name="farm_name"
                           class="form-control"
                           value="<?= htmlspecialchars($farm_name) ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Farm Location</label>

                    <textarea name="location"
                              class="form-control"
                              rows="3"
                              required><?= htmlspecialchars($location) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Farm Area (Hectares)</label>

                    <input type="number"
                           name="area_hectares"
                           class="form-control"
                           step="0.01"
                           min="0.01"
                           value="<?= htmlspecialchars($area_hectares) ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Soil Type</label>

                    <select name="soil_type_id" class="form-select" required>

                        <option value="">Select Soil Type</option>

                        <?php foreach ($soil_types as $soil) { ?>

                            <option value="<?= $soil['soil_type_id'] ?>"
                                <?= ($soil_type_id == $soil['soil_type_id']) ? 'selected' : '' ?>>

                                <?= htmlspecialchars($soil['soil_type_name']) ?>

                            </option>

                        <?php } ?>

                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Irrigation Type</label>

                    <select name="irrigation_type_id" class="form-select" required>

                        <option value="">Select Irrigation Type</option>

                        <?php foreach ($irrigation_types as $irrigation) { ?>

                            <option value="<?= $irrigation['irrigation_type_id'] ?>"
                                <?= ($irrigation_type_id == $irrigation['irrigation_type_id']) ? 'selected' : '' ?>>

                                <?= htmlspecialchars($irrigation['irrigation_description']) ?>

                            </option>

                        <?php } ?>

                    </select>
                </div>

                <div class="d-flex gap-2 mt-4">

                    <button type="submit" name="submit" class="btn btn-success">
                        Update Farm
                    </button>

                    <a href="index.php" class="btn btn-light">
                        Cancel
                    </a>

                </div>

            </form>
            <?php endif; ?>

        </div>

    </main>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>


<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
auth_require_valid_csrf();


// Connect to the database
$pdo = require __DIR__ . '/../config/database.php';

// Get the farmer ID
$farmer_id = $_GET['id'] ?? '';

// Find the existing farmer
$stmt = $pdo->prepare(
    "SELECT * FROM farmer WHERE farmer_id = :farmer_id"
);

$stmt->execute(['farmer_id' => $farmer_id]);

$farmer = $stmt->fetch();

// Return to the farmer list if the farmer does not exist
if (!$farmer) {
    header("Location: index.php");
    exit;
}

$error = "";

// Get the existing information
$first_name = $farmer['first_name'];
$last_name = $farmer['last_name'];
$contact_number = $farmer['contact_number'] ?? '';
$address = $farmer['address'];

// Check if the form was submitted
if (isset($_POST['submit'])) {

    // Get the updated form values
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $contact_number = trim($_POST['contact_number']);
    $address = trim($_POST['address']);

    // Check required fields
    if (
        empty($first_name) ||
        empty($last_name) ||
        empty($address)
    ) {

        $error = "Please complete all required fields.";

    } elseif ($contact_number !== '' && !preg_match('/^[0-9+() -]{7,20}$/', $contact_number)) {

        $error = 'Enter a valid contact number (7-20 digits; + ( ) - and spaces allowed).';

    } else {

        // Update the farmer information
        $sql = "UPDATE farmer SET
                    first_name = :first_name,
                    last_name = :last_name,
                    contact_number = :contact_number,
                    address = :address,
                    updated_by = :updated_by
                WHERE farmer_id = :farmer_id";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'contact_number' => $contact_number ?: null,
                'address' => $address,
                'updated_by' => $_SESSION['auth_user'],
                'farmer_id' => $farmer_id
            ]);
            header("Location: index.php?updated=1");
            exit;
        } catch (PDOException $e) {
            $error = 'Unable to update the farmer record. Please check the information and try again.';
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
                <h4>Update Farmer</h4>
                <p>Update the registered farmer information.</p>
            </div>

            <a href="index.php" class="btn btn-outline-secondary">
                Back to Farmers
            </a>
        </div>

        <div class="dashboard-card">

            <h5 class="fw-bold mb-4">Update Farmer Information</h5>

            <?php if ($error != "") { ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php } ?>

            <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(auth_csrf_token()) ?>">

                <div class="mb-3">
                    <label class="form-label">First Name</label>

                    <input type="text"
                           name="first_name"
                           class="form-control"
                           maxlength="100"
                           value="<?= htmlspecialchars($first_name) ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Last Name</label>

                    <input type="text"
                           name="last_name"
                           class="form-control"
                           maxlength="100"
                           value="<?= htmlspecialchars($last_name) ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Contact Number</label>

                    <input type="text"
                           name="contact_number"
                           class="form-control"
                           maxlength="30"
                           value="<?= htmlspecialchars($contact_number) ?>"
                           placeholder="Enter contact number">
                </div>

                <div class="mb-3">
                    <label class="form-label">Address</label>

                    <textarea name="address"
                              class="form-control"
                              rows="3"
                              required><?= htmlspecialchars($address) ?></textarea>
                </div>

                <div class="d-flex gap-2 mt-4">

                    <button type="submit" name="submit" class="btn btn-success">
                        Update Farmer
                    </button>

                    <a href="index.php" class="btn btn-light">
                        Cancel
                    </a>

                </div>

            </form>

        </div>

    </main>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

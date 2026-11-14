
<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
auth_require_valid_csrf();


// Connect to the database
$pdo = require __DIR__ . '/../config/database.php';

// Prepare variables for the form
$first_name = '';
$last_name = '';
$contact_number = '';
$address = '';

$error = '';

// Check if the form was submitted
if (isset($_POST['submit'])) {

    // Get the values from the form
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $contact_number = trim($_POST['contact_number']);
    $address = trim($_POST['address']);

    // Check required fields
    if ($first_name == '' || $last_name == '' || $address == '') {

        $error = "Please fill in all required fields.";

    } elseif ($contact_number !== '' && !preg_match('/^[0-9+() -]{7,20}$/', $contact_number)) {

        $error = 'Enter a valid contact number (7-20 digits; + ( ) - and spaces allowed).';

    } else {

        // Insert the new farmer into the database
        $sql = "INSERT INTO farmer
                (first_name, last_name, contact_number, address, created_by, updated_by)
                VALUES
                (:first_name, :last_name, :contact_number, :address, :created_by, :updated_by)";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'contact_number' => $contact_number ?: null,
                'address' => $address,
                'created_by' => $_SESSION['auth_user'],
                'updated_by' => $_SESSION['auth_user']
            ]);
            header("Location: index.php?message=added");
            exit;
        } catch (PDOException $e) {
            $error = 'Unable to save the farmer record. Please check the information and try again.';
        }

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
                <h4>Add Farmer</h4>
                <p>Register a new farmer in the system.</p>
            </div>

            <a href="index.php" class="btn btn-outline-secondary">
                Back to Farmers
            </a>
        </div>

        <div class="dashboard-card">

            <h5 class="fw-bold mb-4">Farmer Information</h5>

            <?php if ($error != '') { ?>

                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php } ?>

            <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(auth_csrf_token()) ?>">

                <div class="mb-3">
                    <label class="form-label">First Name *</label>

                    <input type="text"
                           name="first_name"
                           class="form-control"
                           maxlength="100"
                           value="<?= htmlspecialchars($first_name) ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Last Name *</label>

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
                           value="<?= htmlspecialchars($contact_number) ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label">Address *</label>

                    <textarea name="address"
                              class="form-control"
                              rows="3"
                              required><?= htmlspecialchars($address) ?></textarea>
                </div>

                <button type="submit" name="submit" class="btn btn-success">
                    Save Farmer
                </button>

                <a href="index.php" class="btn btn-outline-secondary">
                    Cancel
                </a>

            </form>

        </div>

    </main>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

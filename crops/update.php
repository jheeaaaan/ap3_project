
<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
auth_require_valid_csrf();

$pdo = require __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/validation.php';

$id = $_GET['id'] ?? '';
$error = '';

$stmt = $pdo->prepare("SELECT * FROM crop WHERE crop_id = :id");
$stmt->execute([':id' => $id]);
$crop = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$crop) {
    include __DIR__ . '/../includes/header.php';
    echo '<div class="app-shell">';
    include __DIR__ . '/../includes/sidebar.php';
    echo '<main class="main-content"><div class="page-content"><div class="alert alert-warning">Crop record not found.</div><a href="index.php" class="btn btn-secondary">Back</a></div></main></div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$crop_name = $crop['crop_name'];
$variety = $crop['variety'];
$crop_category_id = $crop['crop_category_id'];
$growing_period_days = $crop['growing_period_days'];
$season_id = $crop['season_id'];

$categories = $pdo->query(
    "SELECT * FROM crop_category ORDER BY category_name"
)->fetchAll(PDO::FETCH_ASSOC);

$seasons = $pdo->query(
    "SELECT * FROM season ORDER BY season_name"
)->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $crop_name = trim($_POST['crop_name'] ?? '');
    $variety = trim($_POST['variety'] ?? '');
    $crop_category_id = $_POST['crop_category_id'] ?? '';
    $growing_period_days = $_POST['growing_period_days'] ?? '';
    $season_id = $_POST['season_id'] ?? '';

    if (
        $crop_name === '' ||
        $variety === '' ||
        $crop_category_id === '' ||
        $growing_period_days === '' ||
        $season_id === ''
    ) {
        $error = 'Please complete all fields.';
    } elseif (
        !lookup_option_exists($categories, $crop_category_id, 'crop_category_id')
        || !lookup_option_exists($seasons, $season_id, 'season_id')
    ) {
        $error = 'Choose a valid crop category and season from the available options.';
    } elseif (!ctype_digit((string) $growing_period_days) || (int) $growing_period_days < 1) {
        $error = 'Growing period must be a positive whole number.';
    } else {
        try {
            $sql = "UPDATE crop SET
                        crop_name = :crop_name,
                        variety = :variety,
                        crop_category_id = :category_id,
                        growing_period_days = :days,
                        season_id = :season_id,
                        updated_by = :updated_by
                    WHERE crop_id = :id";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':crop_name' => $crop_name,
                ':variety' => $variety,
                ':category_id' => $crop_category_id,
                ':days' => $growing_period_days,
                ':season_id' => $season_id,
                ':updated_by' => $_SESSION['auth_user'],
                ':id' => $id
            ]);

            header('Location: index.php?success=updated');
            exit;

        } catch (PDOException $e) {
            $error = 'Unable to update crop. Please check the information and try again.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-content">
<div class="container-fluid py-4">
    <h2 class="fw-bold text-success">Update Crop</h2>

    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body p-4">

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!$categories || !$seasons): ?>
                <div class="alert alert-info">
                    The crop category and season options are required to edit this crop.
                </div>
            <?php else: ?>
            <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(auth_csrf_token()) ?>">

                <div class="mb-3">
                    <label class="form-label">Crop Name</label>
                    <input type="text" name="crop_name" class="form-control"
                           value="<?php echo htmlspecialchars($crop_name); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Variety</label>
                    <input type="text" name="variety" class="form-control"
                           value="<?php echo htmlspecialchars($variety); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Crop Category</label>
                    <select name="crop_category_id" class="form-select" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['crop_category_id']; ?>"
                                <?php echo ($crop_category_id == $category['crop_category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Growing Period (Days)</label>
                    <input type="number" name="growing_period_days" min="1"
                           class="form-control"
                           value="<?php echo htmlspecialchars($growing_period_days); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Season</label>
                    <select name="season_id" class="form-select" required>
                        <option value="">Select Season</option>
                        <?php foreach ($seasons as $season): ?>
                            <option value="<?php echo $season['season_id']; ?>"
                                <?php echo ($season_id == $season['season_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($season['season_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-success">Update Crop</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>

            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

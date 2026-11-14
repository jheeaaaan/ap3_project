
<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = require __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? '';

$stmt = $pdo->prepare(
    "SELECT
        crop.*,
        crop_category.category_name,
        season.season_name
     FROM crop
     INNER JOIN crop_category
        ON crop.crop_category_id = crop_category.crop_category_id
     INNER JOIN season
        ON crop.season_id = season.season_id
     WHERE crop.crop_id = :id"
);

$stmt->execute([':id' => $id]);
$crop = $stmt->fetch(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-content">
<div class="container-fluid py-4">
    <h2 class="fw-bold text-success">Crop Details</h2>

    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body p-4">

            <?php if ($crop): ?>

                <p><strong>Crop ID:</strong> <?php echo htmlspecialchars($crop['crop_id']); ?></p>
                <p><strong>Crop Name:</strong> <?php echo htmlspecialchars($crop['crop_name']); ?></p>
                <p><strong>Variety:</strong> <?php echo htmlspecialchars($crop['variety']); ?></p>
                <p><strong>Category:</strong> <?php echo htmlspecialchars($crop['category_name']); ?></p>
                <p><strong>Growing Period:</strong> <?php echo htmlspecialchars($crop['growing_period_days']); ?> days</p>
                <p><strong>Season:</strong> <?php echo htmlspecialchars($crop['season_name']); ?></p>

                <a href="update.php?id=<?php echo $crop['crop_id']; ?>" class="btn btn-primary">Update</a>
                <a href="index.php" class="btn btn-secondary">Back</a>

            <?php else: ?>
                <div class="alert alert-warning">Crop record not found.</div>
                <a href="index.php" class="btn btn-secondary">Back</a>
            <?php endif; ?>

        </div>
    </div>
</div>

        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

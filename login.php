<?php
require_once __DIR__ . '/includes/auth.php';

if (auth_is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$notice = '';
$selectedRole = '';
$accounts = auth_accounts();
if (($_GET['message'] ?? '') === 'expired' || !empty($_SESSION['auth_expired'])) {
    $notice = 'Your session expired. Please sign in again.';
    unset($_SESSION['auth_expired']);
} elseif (($_GET['message'] ?? '') === 'logged_out') {
    $notice = 'You have been signed out.';
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $username = (string) ($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    foreach ($accounts as $account) {
        if (strcasecmp($username, $account['username']) === 0) {
            $selectedRole = $account['role'];
            break;
        }
    }
    if (!auth_valid_csrf()) {
        $error = 'This sign-in request expired. Please try again.';
    } elseif ($accounts === []) {
        $error = 'Sign-in is unavailable because authentication is not configured.';
    } elseif (auth_login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'The role or password is incorrect.';
    }
}
$page_title = 'Sign in';
include __DIR__ . '/includes/header.php';
?>
<style>
    body {
        background:
            radial-gradient(circle at 15% 10%, #e3f0e0 0, transparent 45%),
            radial-gradient(circle at 90% 90%, #d5e8d3 0, transparent 45%),
            linear-gradient(135deg, #eef6ea 0%, #dcebd8 100%);
        background-attachment: fixed;
    }
    .login-card { background: rgba(255, 255, 255, .78); backdrop-filter: blur(4px); }
    .role-picker { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
    .role-option { position: relative; margin: 0; }
    .role-option input { position: absolute; opacity: 0; inset: 0; cursor: pointer; }
    .role-option .role-card {
        display: flex; flex-direction: column; align-items: center; gap: .25rem;
        padding: 1rem .5rem; text-align: center; cursor: pointer;
        border: 2px solid #cfe0cb; border-radius: 1rem; background: #f4f9f1;
        color: var(--green-dark); transition: all .15s ease;
    }
    .role-option .role-card i { font-size: 1.8rem; }
    .role-option .role-card small { color: var(--muted); }
    .role-option:hover .role-card { border-color: var(--green); background: #eaf4e6; transform: translateY(-2px); }
    .role-option input:checked + .role-card {
        border-color: var(--green-dark); background: var(--green-dark); color: #fff;
        box-shadow: 0 8px 20px rgba(41, 75, 57, .25);
    }
    .role-option input:checked + .role-card small { color: #d6e8d6; }
    .role-option input:focus-visible + .role-card { outline: 3px solid var(--gold); outline-offset: 2px; }
</style>
<main class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
    <section class="card login-card shadow-sm border-0 p-4 p-md-5" style="width: min(100%, 480px); border-radius: 1.25rem;">
        <div class="text-center mb-4">
            <div class="display-6 text-success mb-2"><i class="bi bi-flower1"></i></div>
            <p class="text-uppercase small fw-semibold text-success mb-1">Agriculture Information Systems</p>
            <h1 class="h2 fw-bold">Welcome back</h1>
            <p class="text-secondary mb-0">Choose your role, then sign in.</p>
        </div>
        <?php if ($notice !== ''): ?><div class="alert alert-info" role="status"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($accounts === []): ?>
            <div class="alert alert-warning" role="alert">
                Configure the Administrator and Staff authentication environment variables before signing in.
            </div>
        <?php endif; ?>
        <form method="post" action="login.php" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(auth_csrf_token()) ?>">
            <p class="form-label fw-semibold mb-2">I am signing in as</p>
            <div class="role-picker mb-4">
                <?php foreach ($accounts as $account): ?>
                    <?php $role = $account['role']; ?>
                    <label class="role-option">
                        <input type="radio" name="username" value="<?= htmlspecialchars($account['username'], ENT_QUOTES, 'UTF-8') ?>" required <?= $selectedRole === $role ? 'checked' : '' ?> <?= $accounts === [] ? 'disabled' : '' ?>>
                        <span class="role-card">
                            <i class="bi <?= $role === 'Administrator' ? 'bi-shield-lock' : 'bi-person-badge' ?>"></i>
                            <strong><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></strong>
                            <small><?= $role === 'Administrator' ? 'Full access' : 'Manage records' ?></small>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password">Password</label>
                <input class="form-control form-control-lg" id="password" name="password" type="password" required autocomplete="current-password">
            </div>
            <button class="btn btn-success btn-lg w-100" type="submit" style="background: var(--green-dark); border-color: var(--green-dark);" <?= $accounts === [] ? 'disabled' : '' ?>>Sign in <i class="bi bi-arrow-right ms-1"></i></button>
        </form>
    </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>

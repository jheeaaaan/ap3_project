<?php
require_once __DIR__ . '/includes/auth.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !auth_valid_csrf()) {
    http_response_code(400);
    exit('Invalid logout request.');
}
auth_destroy_session();
header('Location: login.php?message=logged_out');
exit;

<?php
// Shared page setup. Set $page_title before including this file when needed.
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/app_config.php';
    define('BASE_URL', app_base_path());
}
$page_title = $page_title ?? 'Agriculture Information Systems';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> | Agriculture Information Systems</title>
    <link href="<?= BASE_URL ?>/assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body<?php
$current_module = basename(dirname(str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '')));
if (in_array($current_module, ['farmers', 'farms', 'crops', 'planting', 'harvest'], true)) {
    echo ' data-module="' . htmlspecialchars($current_module, ENT_QUOTES, 'UTF-8') . '"';
}
?>>

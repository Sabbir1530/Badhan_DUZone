<?php
/**
 * Header Template
 * Include this at the top of every page.
 * 
 * Pages should set $pageTitle BEFORE including this file.
 * Config is loaded via auth.php -> db.php -> config.php
 */
require_once __DIR__ . '/auth.php';

// Now APP_NAME is available. Build the full page title.
if (isset($pageTitle) && $pageTitle !== APP_NAME) {
    $pageTitle = $pageTitle . ' - ' . APP_NAME;
} else {
    $pageTitle = APP_NAME;
}

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<?php if (isLoggedIn()): ?>
<header class="navbar">
    <a href="<?= BASE_URL ?>dashboard.php" class="brand">
        <span>🩸</span> Badhan DU Zone
    </a>
    <button class="menu-toggle" onclick="document.querySelector('.nav-links').classList.toggle('open')">☰</button>
    <nav class="nav-links">
        <span class="user-info">
            <?= htmlspecialchars(getUserName()) ?>
            <span class="badge"><?= htmlspecialchars(getUserRole()) ?></span>
        </span>
        <a href="<?= BASE_URL ?>dashboard.php" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <a href="<?= BASE_URL ?>requisition_form.php" class="<?= $currentPage === 'requisition_form' ? 'active' : '' ?>">New Requisition</a>
        <a href="<?= BASE_URL ?>requisition_list.php" class="<?= $currentPage === 'requisition_list' ? 'active' : '' ?>">
            <?= isAdmin() ? 'All Requisitions' : 'My Requisitions' ?>
        </a>
        <?php if (isAdmin()): ?>
        <a href="<?= BASE_URL ?>admin_report.php" class="<?= $currentPage === 'admin_report' ? 'active' : '' ?>">Reports</a>
        <a href="<?= BASE_URL ?>manage_users.php" class="<?= $currentPage === 'manage_users' ? 'active' : '' ?>">Users</a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>blood_info.php" class="<?= $currentPage === 'blood_info' ? 'active' : '' ?>">Blood Info</a>
        <a href="<?= BASE_URL ?>logout.php">Logout</a>
    </nav>
</header>
<?php endif; ?>

<?php
/**
 * Dashboard
 */
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$pdo = getDBConnection();
$today = date('Y-m-d');

// Today's stats
if (isAdmin()) {
    $todayReqs = getAllRequisitionsByDate($today);
} else {
    $todayReqs = getRequisitionsByDate($today, getUserId());
}
$todayCount = count($todayReqs);
$todayManaged = count(array_filter($todayReqs, fn($r) => $r['comment'] === 'Managed'));
$todayReferred = count(array_filter($todayReqs, fn($r) => $r['comment'] === 'Referred'));
$todayPending = $todayCount - $todayManaged - $todayReferred;

// Total stats (admin only)
if (isAdmin()) {
    $totalStmt = $pdo->query("SELECT COUNT(*) FROM requisitions");
    $managedStmt = $pdo->query("SELECT COUNT(*) FROM requisitions WHERE comment = 'Managed'");
    $totalAll = (int) $totalStmt->fetchColumn();
    $totalManaged = (int) $managedStmt->fetchColumn();
}
?>

<div class="container">
    <?php
    $flash = getFlash();
    if ($flash): ?>
        <div class="flash <?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <h2 style="margin-bottom: 1.5rem; color: var(--secondary);">
        Welcome, <?= htmlspecialchars(getUserName()) ?>!
        <span class="badge" style="font-size: 0.75rem;"><?= htmlspecialchars(ucfirst(getUserRole())) ?></span>
    </h2>

    <!-- Today's Overview -->
    <div class="dashboard-grid">
        <div class="dash-stat">
            <div class="number"><?= $todayCount ?></div>
            <div class="label">Today's Requisitions</div>
        </div>
        <div class="dash-stat" style="border-top-color: var(--success);">
            <div class="number" style="color: var(--success);"><?= $todayManaged ?></div>
            <div class="label">Today Managed</div>
        </div>
        <div class="dash-stat" style="border-top-color: var(--warning);">
            <div class="number" style="color: var(--warning);"><?= $todayReferred ?></div>
            <div class="label">Today Referred</div>
        </div>
        <div class="dash-stat" style="border-top-color: var(--gray);">
            <div class="number" style="color: var(--gray);"><?= $todayPending ?></div>
            <div class="label">Pending</div>
        </div>
    </div>

    <!-- Totals (Admin Only) -->
    <?php if (isAdmin()): ?>
    <div class="dashboard-grid">
        <div class="dash-stat" style="border-top-color: var(--info);">
            <div class="number" style="color: var(--info);"><?= $totalAll ?></div>
            <div class="label">Total Requisitions (All Time)</div>
        </div>
        <div class="dash-stat" style="border-top-color: var(--success);">
            <div class="number" style="color: var(--success);"><?= $totalManaged ?></div>
            <div class="label">Total Managed (All Time)</div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Blood Group Stats for Today -->
    <?php if (!empty($todayReqs)): 
        $bgStats = calculateBloodGroupStats($todayReqs);
    ?>
    <div class="card">
        <div class="card-header">Today's Blood Group Statistics</div>
        <div class="stats-bar">
            <?php foreach ($bgStats as $bg => $s): ?>
                <?php if ($s['total'] > 0): ?>
                <div class="stat-badge">
                    <span class="bg-label"><?= $bg ?></span>
                    (<span class="stat-num"><?= $s['managed'] ?></span>/<span class="stat-total"><?= $s['total'] ?></span>)
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">Quick Actions</div>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="<?= BASE_URL ?>requisition_form.php" class="btn btn-primary">➕ New Requisition</a>
            <a href="<?= BASE_URL ?>requisition_list.php" class="btn btn-info">📋 View Requisitions</a>
            <?php if (isAdmin()): ?>
            <a href="<?= BASE_URL ?>admin_report.php" class="btn btn-success">📊 Reports & PDF</a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>blood_info.php" class="btn btn-warning">🩸 Blood Info</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

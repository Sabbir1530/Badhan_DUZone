<?php
/**
 * Admin Report Page - PDF Export
 * Only accessible by admin.
 */
$pageTitle = 'Reports';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
requireAdmin();

$filterDate  = $_GET['date'] ?? date('Y-m-d');
$filterMonth = $_GET['month'] ?? '';
$viewMode    = $_GET['view'] ?? 'day';

if ($viewMode === 'month' && !empty($filterMonth)) {
    $requisitions = getAllRequisitionsByMonth($filterMonth);
    $displayLabel = date('F Y', strtotime($filterMonth . '-01'));
} else {
    $requisitions = getAllRequisitionsByDate($filterDate);
    $displayLabel = date('d M Y', strtotime($filterDate));
}

$stats = calculateBloodGroupStats($requisitions);
?>

<div class="container">
    <?php
    $flash = getFlash();
    if ($flash): ?>
        <div class="flash <?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">📊 Admin Reports & PDF Export</div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <form method="GET" action="" style="display:flex; align-items:center; gap:0.8rem; flex-wrap:wrap;">
                <select name="view" class="form-control" onchange="toggleReportView(this.value)">
                    <option value="day" <?= $viewMode === 'day' ? 'selected' : '' ?>>Day Report</option>
                    <option value="month" <?= $viewMode === 'month' ? 'selected' : '' ?>>Month Report</option>
                </select>

                <div id="rpt-day-filter" style="<?= $viewMode === 'month' ? 'display:none;' : '' ?>">
                    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filterDate) ?>">
                </div>
                <div id="rpt-month-filter" style="<?= $viewMode !== 'month' ? 'display:none;' : '' ?>">
                    <input type="month" name="month" class="form-control" value="<?= htmlspecialchars($filterMonth ?: date('Y-m')) ?>">
                </div>

                <button type="submit" class="btn btn-info btn-sm">🔍 View Report</button>
            </form>

            <?php if (!empty($requisitions)): ?>
            <a href="<?= BASE_URL ?>pdf_export.php?view=<?= $viewMode ?>&date=<?= urlencode($filterDate) ?>&month=<?= urlencode($filterMonth) ?>" 
               class="btn btn-danger btn-sm" target="_blank">
                📄 Download PDF
            </a>
            <?php endif; ?>
        </div>

        <!-- Blood Group Statistics -->
        <?php if (!empty($requisitions)): ?>
        <div class="stats-bar">
            <?php foreach ($stats as $bg => $s): ?>
                <?php if ($s['total'] > 0): ?>
                <div class="stat-badge">
                    <span class="bg-label"><?= $bg ?></span>
                    (<span class="stat-num"><?= $s['managed'] ?></span>/<span class="stat-total"><?= $s['total'] ?></span>)
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
            <div class="stat-badge" style="border-color: var(--secondary);">
                <span class="bg-label">Total</span>
                (<span class="stat-num"><?= array_sum(array_column($stats, 'managed')) ?></span>/<span class="stat-total"><?= count($requisitions) ?></span>)
            </div>
        </div>
        <?php endif; ?>

        <!-- Summary Table -->
        <?php if (!empty($requisitions)): ?>
        <div class="section-title">Summary — <?= htmlspecialchars($displayLabel) ?></div>
        <div class="dashboard-grid" style="margin-bottom: 1.5rem;">
            <div class="dash-stat">
                <div class="number"><?= count($requisitions) ?></div>
                <div class="label">Total Requisitions</div>
            </div>
            <div class="dash-stat" style="border-top-color: var(--success);">
                <div class="number" style="color: var(--success);">
                    <?= array_sum(array_column($stats, 'managed')) ?>
                </div>
                <div class="label">Managed</div>
            </div>
            <div class="dash-stat" style="border-top-color: var(--warning);">
                <div class="number" style="color: var(--warning);">
                    <?= count(array_filter($requisitions, fn($r) => $r['comment'] === 'Referred')) ?>
                </div>
                <div class="label">Referred</div>
            </div>
            <div class="dash-stat" style="border-top-color: var(--gray);">
                <div class="number" style="color: var(--gray);">
                    <?= count(array_filter($requisitions, fn($r) => empty($r['comment']))) ?>
                </div>
                <div class="label">Pending</div>
            </div>
        </div>

        <!-- Full Data Table -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date/Time</th>
                        <th>Patient</th>
                        <th>Age</th>
                        <th>Blood</th>
                        <th>Qty</th>
                        <th>Component</th>
                        <th>Hospital</th>
                        <th>Problem</th>
                        <th>Attendant</th>
                        <th>Att. BG</th>
                        <th>Address</th>
                        <th>Contact</th>
                        <th>Comment</th>
                        <th>Managed By</th>
                        <th>Submitted By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requisitions as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= date('d M h:i A', strtotime($r['created_at'])) ?></td>
                        <td><?= htmlspecialchars($r['patient_name']) ?></td>
                        <td><?= $r['patient_age'] ?></td>
                        <td><strong><?= htmlspecialchars($r['blood_group']) ?></strong></td>
                        <td><?= $r['quantity'] ?></td>
                        <td><?= htmlspecialchars($r['component']) ?></td>
                        <td><?= htmlspecialchars($r['hospital_name']) ?></td>
                        <td><?= htmlspecialchars(mb_strimwidth($r['problem'], 0, 50, '...')) ?></td>
                        <td><?= htmlspecialchars($r['attendant_name']) ?></td>
                        <td><?= htmlspecialchars($r['attendant_blood_group']) ?></td>
                        <td><?= htmlspecialchars($r['attendant_address'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['attendant_contact']) ?></td>
                        <td>
                            <?php
                            $c = $r['comment'] ?? '';
                            if ($c === 'Managed') echo '<span class="status-managed">Managed</span>';
                            elseif ($c === 'Referred') echo '<span class="status-referred">Referred</span>';
                            elseif ($c === 'Others') echo '<span class="status-others">Others</span>';
                            else echo '<span class="status-pending">Pending</span>';
                            ?>
                        </td>
                        <td><?= htmlspecialchars($r['managed_by'] ?: '—') ?></td>
                        <td><?= htmlspecialchars($r['creator_name']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="flash info">No requisitions found for the selected period.</div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleReportView(view) {
    document.getElementById('rpt-day-filter').style.display = view === 'day' ? '' : 'none';
    document.getElementById('rpt-month-filter').style.display = view === 'month' ? '' : 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

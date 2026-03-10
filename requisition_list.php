<?php
/**
 * Requisition List Page
 * - Members see their own requisitions (daywise).
 * - Admin sees all requisitions (daywise or monthwise).
 * - Members can update comment/managed_by on their own entries.
 * - Privacy: Members can't see others' comment/managed_by until they submit their own.
 */
$pageTitle = 'Requisitions';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

// Handle comment/managed_by update (AJAX or form POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_comment') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid form submission.');
    } else {
        $reqId     = (int) ($_POST['req_id'] ?? 0);
        $comment   = $_POST['comment'] ?? '';
        $managedBy = trim($_POST['managed_by'] ?? '');

        if (!in_array($comment, ['Managed', 'Referred', 'Others'])) {
            setFlash('error', 'Invalid comment type.');
        } else {
            $req = getRequisitionById($reqId);
            if ($req && $req['created_by'] == getUserId()) {
                updateRequisitionComment($reqId, $comment, $managedBy, getUserId());
                setFlash('success', 'Requisition #' . $reqId . ' updated successfully.');
            } else {
                setFlash('error', 'You can only update your own requisitions.');
            }
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Determine date filter
$filterDate  = $_GET['date'] ?? date('Y-m-d');
$filterMonth = $_GET['month'] ?? '';
$viewMode    = $_GET['view'] ?? 'day'; // 'day' or 'month' (admin only)

if (isAdmin() && $viewMode === 'month' && !empty($filterMonth)) {
    $requisitions = getAllRequisitionsByMonth($filterMonth);
    $displayLabel = date('F Y', strtotime($filterMonth . '-01'));
} elseif (isAdmin()) {
    $requisitions = getAllRequisitionsByDate($filterDate);
    $displayLabel = date('d M Y', strtotime($filterDate));
} else {
    // Member: get own + others with privacy
    $requisitions = getRequisitionsByDate($filterDate, getUserId());
    $displayLabel = date('d M Y', strtotime($filterDate));
}

$stats = calculateBloodGroupStats($requisitions);
$csrfToken = generateCSRFToken();
?>

<div class="container">
    <?php
    $flash = getFlash();
    if ($flash): ?>
        <div class="flash <?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            📋 <?= isAdmin() ? 'All Requisitions' : 'My Requisitions' ?> — <?= htmlspecialchars($displayLabel) ?>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <form method="GET" action="" style="display:flex; align-items:center; gap:0.8rem; flex-wrap:wrap;">
                <?php if (isAdmin()): ?>
                <select name="view" class="form-control" onchange="toggleViewFields(this.value)">
                    <option value="day" <?= $viewMode === 'day' ? 'selected' : '' ?>>Day View</option>
                    <option value="month" <?= $viewMode === 'month' ? 'selected' : '' ?>>Month View</option>
                </select>
                <?php endif; ?>

                <div id="day-filter" style="<?= ($viewMode === 'month' && isAdmin()) ? 'display:none;' : '' ?>">
                    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filterDate) ?>">
                </div>

                <?php if (isAdmin()): ?>
                <div id="month-filter" style="<?= $viewMode !== 'month' ? 'display:none;' : '' ?>">
                    <input type="month" name="month" class="form-control" value="<?= htmlspecialchars($filterMonth ?: date('Y-m')) ?>">
                </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-info btn-sm">🔍 Filter</button>
            </form>
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

        <!-- Requisition Table -->
        <?php if (empty($requisitions)): ?>
            <div class="flash info">No requisitions found for the selected period.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Age</th>
                        <th>Blood</th>
                        <th>Qty</th>
                        <th>Component</th>
                        <th>Hospital</th>
                        <th>Problem</th>
                        <th>Attendant</th>
                        <th>Blood Group</th>
                        <th>Address</th>
                        <th>Contact</th>
                        <?php if (isAdmin()): ?><th>Submitted By</th><?php endif; ?>
                        <th>Comment</th>
                        <th>Managed By</th>
                        <th>Time</th>
                        <?php if (!isAdmin()): ?><th>Action</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requisitions as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($r['patient_name']) ?></td>
                        <td><?= $r['patient_age'] ?></td>
                        <td><strong><?= htmlspecialchars($r['blood_group']) ?></strong></td>
                        <td><?= $r['quantity'] ?></td>
                        <td><?= htmlspecialchars($r['component']) ?></td>
                        <td><?= htmlspecialchars($r['hospital_name']) ?></td>
                        <td><?= htmlspecialchars(mb_strimwidth($r['problem'], 0, 40, '...')) ?></td>
                        <td><?= htmlspecialchars($r['attendant_name']) ?></td>
                         <td><?= htmlspecialchars($r['attendant_blood_group']) ?></td>
                        <td><?= htmlspecialchars($r['attendant_address'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['attendant_contact']) ?></td>
                        <?php if (isAdmin()): ?>
                            <td><?= htmlspecialchars($r['creator_name']) ?></td>
                        <?php endif; ?>
                        <td>
                            <?php
                            $commentVal = $r['comment'] ?? '';
                            if ($commentVal === 'Managed') echo '<span class="status-managed">Managed</span>';
                            elseif ($commentVal === 'Referred') echo '<span class="status-referred">Referred</span>';
                            elseif ($commentVal === 'Others') echo '<span class="status-others">Others</span>';
                            elseif ($commentVal === '—') echo '<span class="status-pending">—</span>';
                            else echo '<span class="status-pending">Pending</span>';
                            ?>
                        </td>
                        <td><?= htmlspecialchars($r['managed_by'] ?: ($commentVal === '—' ? '—' : '—')) ?></td>
                        <td><?= date('h:i A', strtotime($r['created_at'])) ?></td>
                        <?php if (!isAdmin()): ?>
                        <td>
                            <?php if ($r['created_by'] == getUserId() && (empty($r['comment']) || $r['comment'] === '')): ?>
                                <button class="btn btn-success btn-sm" 
                                        onclick="openUpdateModal(<?= $r['id'] ?>, '<?= htmlspecialchars($r['patient_name']) ?>')">
                                    ✏️ Update
                                </button>
                            <?php elseif ($r['created_by'] == getUserId()): ?>
                                <button class="btn btn-warning btn-sm" 
                                        onclick="openUpdateModal(<?= $r['id'] ?>, '<?= htmlspecialchars($r['patient_name']) ?>', '<?= htmlspecialchars($r['comment']) ?>', '<?= htmlspecialchars($r['managed_by']) ?>')">
                                    ✏️ Edit
                                </button>
                            <?php else: ?>
                                <span style="color: var(--gray); font-size: 0.8rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Update Comment Modal -->
<?php if (!isAdmin()): ?>
<div id="updateModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
     background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
    <div style="background:white; border-radius:12px; padding:2rem; max-width:450px; width:90%; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
        <h3 style="margin-bottom:1rem; color:var(--secondary);">Update Requisition</h3>
        <p id="modalPatientName" style="margin-bottom:1rem; color:var(--gray);"></p>
        <form method="POST" action="">
            <input type="hidden" name="action" value="update_comment">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="req_id" id="modalReqId">
            
            <div class="form-group">
                <label>Comment *</label>
                <select name="comment" id="modalComment" class="form-control" required>
                    <option value="">-- Select --</option>
                    <option value="Managed">Managed</option>
                    <option value="Referred">Referred</option>
                    <option value="Others">Others</option>
                </select>
            </div>
            <div class="form-group">
                <label>Managed By</label>
                <input type="text" name="managed_by" id="modalManagedBy" class="form-control" placeholder="Name of the person">
            </div>
            <div style="display:flex; gap:0.8rem; margin-top:1rem;">
                <button type="submit" class="btn btn-success">Save</button>
                <button type="button" class="btn btn-secondary" onclick="closeUpdateModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openUpdateModal(id, patientName, comment, managedBy) {
    document.getElementById('modalReqId').value = id;
    document.getElementById('modalPatientName').textContent = 'Patient: ' + patientName;
    document.getElementById('modalComment').value = comment || '';
    document.getElementById('modalManagedBy').value = managedBy || '';
    document.getElementById('updateModal').style.display = 'flex';
}
function closeUpdateModal() {
    document.getElementById('updateModal').style.display = 'none';
}
// Close modal on outside click
document.getElementById('updateModal').addEventListener('click', function(e) {
    if (e.target === this) closeUpdateModal();
});
</script>
<?php endif; ?>

<?php if (isAdmin()): ?>
<script>
function toggleViewFields(view) {
    document.getElementById('day-filter').style.display = view === 'day' ? '' : 'none';
    document.getElementById('month-filter').style.display = view === 'month' ? '' : 'none';
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

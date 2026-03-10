<?php
/**
 * Blood Donation Info Page
 * Shows blood component info and Zone Committee contacts.
 */
$pageTitle = 'Blood Info';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$committee = getZoneCommittee();
?>

<div class="container">
    <?php
    $flash = getFlash();
    if ($flash): ?>
        <div class="flash <?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <!-- Blood Components Information -->
    <div class="card">
        <div class="card-header">🩸 Blood Components</div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Component</th>
                        <th>Description</th>
                        <th>Common Uses</th>
                        <th>Storage</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td><strong>Whole Blood</strong></td>
                        <td>Contains all blood components — RBCs, WBCs, platelets, and plasma</td>
                        <td>Massive hemorrhage, exchange transfusion</td>
                        <td>2–6 °C, up to 35 days</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td><strong>RCC / PCV / PRBC</strong></td>
                        <td>Red Cell Concentrate — packed red blood cells with most plasma removed</td>
                        <td>Anemia, surgical blood loss, chronic transfusion</td>
                        <td>2–6 °C, up to 42 days</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td><strong>Platelet Concentrate</strong></td>
                        <td>Concentrated platelets separated from whole blood or by apheresis</td>
                        <td>Thrombocytopenia, dengue, leukemia, chemotherapy</td>
                        <td>20–24 °C with agitation, up to 5 days</td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td><strong>FFP (Fresh Frozen Plasma)</strong></td>
                        <td>Plasma frozen within 8 hours of collection; contains all clotting factors</td>
                        <td>Coagulation disorders, liver disease, DIC, plasma exchange</td>
                        <td>≤ −18 °C, up to 1 year</td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td><strong>Cryoprecipitate</strong></td>
                        <td>Cold-insoluble portion of FFP; rich in fibrinogen, Factor VIII, vWF, Factor XIII</td>
                        <td>Hemophilia A, von Willebrand disease, hypofibrinogenemia</td>
                        <td>≤ −18 °C, up to 1 year</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Zone Committee -->
    <div class="card">
        <div class="card-header">👥 Zone Contacts</div>

        <?php if (empty($committee)): ?>
            <div class="flash info">No data available.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Designation</th>
                        <th>Name</th>
                        <th>Contact Number</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($committee as $i => $c): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= htmlspecialchars($c['role']) ?></strong></td>
                        <td><?= htmlspecialchars($c['name']) ?></td>
                        <td>
                            <a href="tel:<?= htmlspecialchars($c['contact']) ?>" style="color: var(--info); text-decoration: none;">
                                📞 <?= htmlspecialchars($c['contact']) ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
        <div style="margin-top: 1rem;">
            <button class="btn btn-primary btn-sm" onclick="document.getElementById('updateCommitteeModal').style.display='flex'">
                ✏️ Edit Committee
            </button>
            <button class="btn btn-success btn-sm" onclick="document.getElementById('addCommitteeModal').style.display='flex'">
                ➕ Add Member
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Blood Group Compatibility Chart -->
    <div class="card">
        <div class="card-header">📋 Blood Group Compatibility</div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Blood Group</th>
                        <th>Can Donate To</th>
                        <th>Can Receive From</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td><strong>A+</strong></td><td>A+, AB+</td><td>A+, A-, O+, O-</td></tr>
                    <tr><td><strong>A-</strong></td><td>A+, A-, AB+, AB-</td><td>A-, O-</td></tr>
                    <tr><td><strong>B+</strong></td><td>B+, AB+</td><td>B+, B-, O+, O-</td></tr>
                    <tr><td><strong>B-</strong></td><td>B+, B-, AB+, AB-</td><td>B-, O-</td></tr>
                    <tr><td><strong>AB+</strong></td><td>AB+</td><td>All Groups (Universal Recipient)</td></tr>
                    <tr><td><strong>AB-</strong></td><td>AB+, AB-</td><td>A-, B-, AB-, O-</td></tr>
                    <tr><td><strong>O+</strong></td><td>A+, B+, AB+, O+</td><td>O+, O-</td></tr>
                    <tr><td><strong>O-</strong></td><td>All Groups (Universal Donor)</td><td>O-</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Update Zone Committee Modal (Admin Only) -->
<?php if (isAdmin()): ?>
<div id="updateCommitteeModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
     background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center; overflow-y:auto;">
    <div style="background:white; border-radius:12px; padding:2rem; max-width:600px; width:90%; margin:2rem auto; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
        <h3 style="margin-bottom:1rem; color:var(--secondary);">✏️ Edit Zone Committee</h3>
        <form method="POST" action="<?= BASE_URL ?>update_blood_info.php">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="form_type" value="committee_update">
            <?php foreach ($committee as $c): ?>
            <div style="border:1px solid var(--border); border-radius:8px; padding:1rem; margin-bottom:0.8rem;">
                <div class="form-row-3">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Designation</label>
                        <input type="text" name="comm[<?= $c['id'] ?>][role]" class="form-control" 
                               value="<?= htmlspecialchars($c['role']) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Name</label>
                        <input type="text" name="comm[<?= $c['id'] ?>][name]" class="form-control" 
                               value="<?= htmlspecialchars($c['name']) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Contact</label>
                        <input type="text" name="comm[<?= $c['id'] ?>][contact]" class="form-control" 
                               value="<?= htmlspecialchars($c['contact']) ?>" required>
                    </div>
                </div>
                <div style="text-align:right; margin-top:0.5rem;">
                    <button type="button" class="btn btn-danger btn-sm" 
                            onclick="if(confirm('Delete this committee member?')) { document.getElementById('deleteComm<?= $c['id'] ?>').submit(); }">
                        🗑️ Delete
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            <div style="display:flex; gap:0.8rem; margin-top:1rem;">
                <button type="submit" class="btn btn-success">Save All</button>
                <button type="button" class="btn btn-secondary" 
                        onclick="document.getElementById('updateCommitteeModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Zone Committee Member Modal -->
<div id="addCommitteeModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
     background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
    <div style="background:white; border-radius:12px; padding:2rem; max-width:500px; width:90%; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
        <h3 style="margin-bottom:1rem; color:var(--secondary);">➕ Add Committee Member</h3>
        <form method="POST" action="<?= BASE_URL ?>update_blood_info.php">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="form_type" value="committee_add">
            <div class="form-group">
                <label>Designation *</label>
                <input type="text" name="role" class="form-control" placeholder="e.g. President" required>
            </div>
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" class="form-control" placeholder="Full name" required>
            </div>
            <div class="form-group">
                <label>Contact Number *</label>
                <input type="text" name="contact" class="form-control" placeholder="01XXXXXXXXX" required>
            </div>
            <div style="display:flex; gap:0.8rem; margin-top:1rem;">
                <button type="submit" class="btn btn-success">Add Member</button>
                <button type="button" class="btn btn-secondary" 
                        onclick="document.getElementById('addCommitteeModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Hidden delete forms for committee members -->
<?php foreach ($committee as $c): ?>
<form id="deleteComm<?= $c['id'] ?>" method="POST" action="<?= BASE_URL ?>update_blood_info.php" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="form_type" value="committee_delete">
    <input type="hidden" name="delete_id" value="<?= $c['id'] ?>">
</form>
<?php endforeach; ?>

<?php endif; ?>

<script>
// Close modals on outside click
['updateCommitteeModal','addCommitteeModal'].forEach(function(id){
    var el = document.getElementById(id);
    if(el) el.addEventListener('click', function(e){ if(e.target===this) this.style.display='none'; });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

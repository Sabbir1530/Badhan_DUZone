<?php
/**
 * Requisition Form - Submit New Requisition
 */
$pageTitle = 'New Requisition';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    // Collect and validate
    $old = $_POST;

    $patient_name = trim($_POST['patient_name'] ?? '');
    $patient_age  = (int) ($_POST['patient_age'] ?? '');
    $blood_group  = $_POST['blood_group'] ?? '';
    $quantity     = trim($_POST['quantity'] ?? '');
    $component    = $_POST['component'] ?? '';
    $hospital     = $_POST['hospital_name'] ?? '';
    $hospital_custom = trim($_POST['hospital_custom'] ?? '');
    $problem      = trim($_POST['problem'] ?? '');

    $att_name     = trim($_POST['attendant_name'] ?? '');
    $att_bg       = trim($_POST['attendant_blood_group'] ?? '');
    $att_address  = trim($_POST['attendant_address'] ?? '');
    $att_contact  = trim($_POST['attendant_contact'] ?? '');

    // Validation
    if (empty($patient_name)) $errors[] = 'Patient name is required.';
    if ($patient_age < 0 || $patient_age > 150) $errors[] = 'Valid patient age is required.';
    if (!in_array($blood_group, ['A+','A-','B+','B-','AB+','AB-','O+','O-'])) $errors[] = 'Invalid blood group.';
    if (empty($quantity)) $errors[] = 'Quantity is required.';
    if (!in_array($component, ['Whole Blood','RCC/PCV/PRBC','Platelet','FFP','Cryoprecipitate'])) $errors[] = 'Invalid component.';
    
    // Hospital
    $validHospitals = ['DMC', 'PG', 'Birdem', 'Burn Institute'];
    if ($hospital === 'Other') {
        if (empty($hospital_custom)) {
            $errors[] = 'Please enter the hospital name.';
        } else {
            $hospital = $hospital_custom;
        }
    } elseif (!in_array($hospital, $validHospitals)) {
        $errors[] = 'Invalid hospital selection.';
    }

    if (empty($problem)) $errors[] = 'Problem description is required.';
    if (empty($att_name)) $errors[] = 'Attendant name is required.';
    $validAttBg = ['A+','A-','B+','B-','AB+','AB-','O+','O-',''];
    if (!in_array($att_bg, $validAttBg)) $errors[] = 'Invalid attendant blood group.';
    if ($att_bg === '') $att_bg = null;
    if (empty($att_address)) $errors[] = 'Attendant address is required.';
    if (empty($att_contact)) $errors[] = 'Attendant contact number is required.';

    if (empty($errors)) {
        $id = createRequisition([
            'patient_name'          => $patient_name,
            'patient_age'           => $patient_age,
            'blood_group'           => $blood_group,
            'quantity'              => $quantity,
            'component'             => $component,
            'hospital_name'         => $hospital,
            'problem'               => $problem,
            'attendant_name'        => $att_name,
            'attendant_blood_group' => $att_bg,
            'attendant_address'     => $att_address,
            'attendant_contact'     => $att_contact,
            'created_by'            => getUserId(),
        ]);

        setFlash('success', 'Requisition #' . $id . ' submitted successfully!');
        header('Location: ' . BASE_URL . 'requisition_list.php');
        exit;
    }
}

$csrfToken = generateCSRFToken();
$bloodGroups = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
$components  = ['Whole Blood','RCC/PCV/PRBC','Platelet','FFP','Cryoprecipitate'];
$hospitals   = ['DMC', 'PG', 'Birdem', 'Burn Institute', 'Other'];
?>

<div class="container">
    <div class="card">
        <div class="card-header">📝 New Blood Requisition</div>

        <?php if (!empty($errors)): ?>
            <div class="flash error">
                <ul style="margin:0; padding-left: 1.2rem;">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <!-- Patient Information -->
            <div class="section-title">🏥 Patient Information</div>

            <div class="form-row">
                <div class="form-group">
                    <label for="patient_name">Patient Name *</label>
                    <input type="text" id="patient_name" name="patient_name" class="form-control"
                           value="<?= htmlspecialchars($old['patient_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="patient_age">Age *</label>
                    <input type="text" id="patient_age" name="patient_age" class="form-control"
                           value="<?= htmlspecialchars($old['patient_age'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-row-3">
                <div class="form-group">
                    <label for="blood_group">Blood Group *</label>
                    <select id="blood_group" name="blood_group" class="form-control" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($bloodGroups as $bg): ?>
                            <option value="<?= $bg ?>" <?= ($old['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input type="text" id="quantity" name="quantity" class="form-control"
                           value="<?= htmlspecialchars($old['quantity'] ?? '') ?>" 
                           placeholder="e.g. 2 bags, 1 bag + 1 platelet" required>
                </div>
                <div class="form-group">
                    <label for="component">Component *</label>
                    <select id="component" name="component" class="form-control" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($components as $c): ?>
                            <option value="<?= $c ?>" <?= ($old['component'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="hospital_name">Hospital *</label>
                    <select id="hospital_name" name="hospital_name" class="form-control" required
                            onchange="document.getElementById('custom_hospital_row').style.display = this.value === 'Other' ? 'block' : 'none';">
                        <option value="">-- Select --</option>
                        <?php foreach ($hospitals as $h): ?>
                            <option value="<?= $h ?>" <?= ($old['hospital_name'] ?? '') === $h ? 'selected' : '' ?>><?= $h ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="custom_hospital_row" 
                     style="display: <?= ($old['hospital_name'] ?? '') === 'Other' ? 'block' : 'none' ?>;">
                    <label for="hospital_custom">Hospital Name (Custom)</label>
                    <input type="text" id="hospital_custom" name="hospital_custom" class="form-control"
                           value="<?= htmlspecialchars($old['hospital_custom'] ?? '') ?>"
                           placeholder="Enter hospital name">
                </div>
            </div>

            <div class="form-group">
                <label for="problem">Problem / Diagnosis *</label>
                <textarea id="problem" name="problem" class="form-control" rows="3" required><?= htmlspecialchars($old['problem'] ?? '') ?></textarea>
            </div>

            <!-- Attendant Information -->
            <div class="section-title" style="margin-top: 1rem;">👤 Attendant Information</div>

            <div class="form-row" style="grid-template-columns: 1fr 1fr 1fr 1fr;">
                <div class="form-group">
                    <label for="attendant_name">Attendant Name *</label>
                    <input type="text" id="attendant_name" name="attendant_name" class="form-control"
                           value="<?= htmlspecialchars($old['attendant_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="attendant_blood_group">Attendant Blood Group</label>
                    <select id="attendant_blood_group" name="attendant_blood_group" class="form-control">
                        <option value="">-- Select (Optional) --</option>
                        <?php foreach ($bloodGroups as $bg): ?>
                            <option value="<?= $bg ?>" <?= ($old['attendant_blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                        <?php endforeach; ?>
                        <option value="Don't Know" <?= ($old['attendant_blood_group'] ?? '') === "Don't Know" ? 'selected' : '' ?>>Don't Know</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="attendant_address">Address *</label>
                    <input type="text" id="attendant_address" name="attendant_address" class="form-control"
                           value="<?= htmlspecialchars($old['attendant_address'] ?? '') ?>" 
                           placeholder="Hall / Area / Address" required>
                </div>
                <div class="form-group">
                    <label for="attendant_contact">Contact Number *</label>
                    <input type="tel" id="attendant_contact" name="attendant_contact" class="form-control"
                           value="<?= htmlspecialchars($old['attendant_contact'] ?? '') ?>" 
                           placeholder="01XXXXXXXXX" required>
                </div>
            </div>

            <div style="margin-top: 1rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary">✅ Submit Requisition</button>
                <a href="<?= BASE_URL ?>dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

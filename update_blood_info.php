<?php
/**
 * Update Blood Info Quantities (Admin Only)
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'blood_info.php');
    exit;
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid form submission.');
    header('Location: ' . BASE_URL . 'blood_info.php');
    exit;
}

$pdo = getDBConnection();
$formType = $_POST['form_type'] ?? '';

// ---- Update Zone Committee ----
if ($formType === 'committee_update') {
    $members = $_POST['comm'] ?? [];
    $stmt = $pdo->prepare("UPDATE zone_contacts SET role = :role, name = :name, contact = :contact WHERE id = :id");
    foreach ($members as $id => $data) {
        $role    = trim($data['role'] ?? '');
        $name    = trim($data['name'] ?? '');
        $contact = trim($data['contact'] ?? '');
        if (!empty($role) && !empty($name) && !empty($contact)) {
            $stmt->execute([
                ':role'    => $role,
                ':name'    => $name,
                ':contact' => $contact,
                ':id'      => (int) $id,
            ]);
        }
    }
    setFlash('success', 'Zone committee updated successfully.');
}

// ---- Add Zone Committee Member ----
if ($formType === 'committee_add') {
    $role    = trim($_POST['role'] ?? '');
    $name    = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    if (!empty($role) && !empty($name) && !empty($contact)) {
        $stmt = $pdo->prepare("INSERT INTO zone_contacts (role, name, contact) VALUES (:role, :name, :contact)");
        $stmt->execute([':role' => $role, ':name' => $name, ':contact' => $contact]);
        setFlash('success', 'Committee member added successfully.');
    } else {
        setFlash('error', 'All fields are required.');
    }
}

// ---- Delete Zone Committee Member ----
if ($formType === 'committee_delete') {
    $deleteId = (int) ($_POST['delete_id'] ?? 0);
    if ($deleteId > 0) {
        $stmt = $pdo->prepare("DELETE FROM zone_contacts WHERE id = :id");
        $stmt->execute([':id' => $deleteId]);
        setFlash('success', 'Committee member deleted.');
    }
}

header('Location: ' . BASE_URL . 'blood_info.php');
exit;

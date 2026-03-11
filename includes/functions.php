<?php
/**
 * Requisition CRUD Functions
 */

require_once __DIR__ . '/db.php';

/**
 * Create a new requisition.
 */
function createRequisition(array $data): int
{
    $pdo = getDBConnection();
    $sql = "INSERT INTO requisitions 
            (patient_name, patient_age, blood_group, quantity, component, hospital_name, problem,
             attendant_name, attendant_blood_group, attendant_address, attendant_contact, created_by)
            VALUES 
            (:patient_name, :patient_age, :blood_group, :quantity, :component, :hospital_name, :problem,
             :attendant_name, :attendant_blood_group, :attendant_address, :attendant_contact, :created_by)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':patient_name'          => $data['patient_name'],
        ':patient_age'           => $data['patient_age'],
        ':blood_group'           => $data['blood_group'],
        ':quantity'              => $data['quantity'],
        ':component'             => $data['component'],
        ':hospital_name'         => $data['hospital_name'],
        ':problem'               => $data['problem'],
        ':attendant_name'        => $data['attendant_name'],
        ':attendant_blood_group' => $data['attendant_blood_group'],
        ':attendant_address'     => $data['attendant_address'],
        ':attendant_contact'     => $data['attendant_contact'],
        ':created_by'            => $data['created_by'],
    ]);
    return (int) $pdo->lastInsertId();
}

/**
 * Update comment and managed_by for a requisition.
 * Only the creator can update their own requisition's comment/managed_by.
 */
function updateRequisitionComment(int $id, string $comment, string $managedBy, int $userId): bool
{
    $pdo = getDBConnection();
    $stmt = $pdo->prepare(
        "UPDATE requisitions SET comment = :comment, managed_by = :managed_by 
         WHERE id = :id AND created_by = :user_id"
    );
    return $stmt->execute([
        ':comment'    => $comment,
        ':managed_by' => $managedBy,
        ':id'         => $id,
        ':user_id'    => $userId,
    ]);
}

/**
 * Admin: Update ALL fields of a requisition.
 */
function updateRequisitionFull(int $id, array $data): bool
{
    $pdo = getDBConnection();
    $stmt = $pdo->prepare(
        "UPDATE requisitions SET 
            patient_name = :patient_name,
            patient_age = :patient_age,
            blood_group = :blood_group,
            quantity = :quantity,
            component = :component,
            hospital_name = :hospital_name,
            problem = :problem,
            attendant_name = :attendant_name,
            attendant_blood_group = :attendant_blood_group,
            attendant_address = :attendant_address,
            attendant_contact = :attendant_contact,
            comment = :comment,
            managed_by = :managed_by
         WHERE id = :id"
    );
    return $stmt->execute([
        ':patient_name'          => $data['patient_name'],
        ':patient_age'           => $data['patient_age'],
        ':blood_group'           => $data['blood_group'],
        ':quantity'              => $data['quantity'],
        ':component'             => $data['component'],
        ':hospital_name'         => $data['hospital_name'],
        ':problem'               => $data['problem'],
        ':attendant_name'        => $data['attendant_name'],
        ':attendant_blood_group' => $data['attendant_blood_group'],
        ':attendant_address'     => $data['attendant_address'],
        ':attendant_contact'     => $data['attendant_contact'],
        ':comment'               => $data['comment'],
        ':managed_by'            => $data['managed_by'],
        ':id'                    => $id,
    ]);
}

/**
 * Get requisitions by date for a specific user.
 */
function getRequisitionsByDate(string $date, int $userId): array
{
    $pdo = getDBConnection();
    $stmt = $pdo->prepare(
        "SELECT r.*, u.name AS creator_name 
         FROM requisitions r 
         JOIN users u ON r.created_by = u.id
         WHERE DATE(r.created_at) = :date AND r.created_by = :user_id
         ORDER BY r.id ASC"
    );
    $stmt->execute([':date' => $date, ':user_id' => $userId]);
    return $stmt->fetchAll();
}

/**
 * Get ALL requisitions by date (admin).
 */
function getAllRequisitionsByDate(string $date): array
{
    $pdo = getDBConnection();
    $stmt = $pdo->prepare(
        "SELECT r.*, u.name AS creator_name 
         FROM requisitions r 
         JOIN users u ON r.created_by = u.id
         WHERE DATE(r.created_at) = :date
         ORDER BY r.id ASC"
    );
    $stmt->execute([':date' => $date]);
    return $stmt->fetchAll();
}

/**
 * Get ALL requisitions by month (admin).
 */
function getAllRequisitionsByMonth(string $yearMonth): array
{
    $pdo = getDBConnection();
    $stmt = $pdo->prepare(
        "SELECT r.*, u.name AS creator_name 
         FROM requisitions r 
         JOIN users u ON r.created_by = u.id
         WHERE DATE_FORMAT(r.created_at, '%Y-%m') = :ym
         ORDER BY r.id ASC"
    );
    $stmt->execute([':ym' => $yearMonth]);
    return $stmt->fetchAll();
}

/**
 * Get a single requisition by ID.
 */
function getRequisitionById(int $id): ?array
{
    $pdo = getDBConnection();
    $stmt = $pdo->prepare(
        "SELECT r.*, u.name AS creator_name 
         FROM requisitions r 
         JOIN users u ON r.created_by = u.id
         WHERE r.id = :id LIMIT 1"
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Calculate blood group statistics for a list of requisitions.
 * Returns: ['A+' => ['managed' => 5, 'total' => 8], ...]
 */
function calculateBloodGroupStats(array $requisitions): array
{
    $groups = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
    $stats = [];
    foreach ($groups as $g) {
        $stats[$g] = ['managed' => 0, 'total' => 0];
    }
    foreach ($requisitions as $r) {
        $bg = $r['blood_group'];
        if (isset($stats[$bg])) {
            $stats[$bg]['total']++;
            if ($r['comment'] === 'Managed') {
                $stats[$bg]['managed']++;
            }
        }
    }
    return $stats;
}

/**
 * Get all zone committee members.
 */
function getZoneCommittee(): array
{
    $pdo = getDBConnection();
    return $pdo->query("SELECT * FROM zone_contacts ORDER BY id")->fetchAll();
}

/**
 * Get requisitions by date for ALL members (admin view with privacy).
 * Admin can see everything. For member view, we filter differently.
 */
function getMemberRequisitionsForDate(string $date, int $currentUserId): array
{
    $pdo = getDBConnection();
    // Get all requisitions for the date
    $stmt = $pdo->prepare(
        "SELECT r.*, u.name AS creator_name 
         FROM requisitions r 
         JOIN users u ON r.created_by = u.id
         WHERE DATE(r.created_at) = :date
         ORDER BY r.id ASC"
    );
    $stmt->execute([':date' => $date]);
    $all = $stmt->fetchAll();

    // Check if current user has submitted their comment for this date
    $hasSubmittedComment = false;
    foreach ($all as $r) {
        if ($r['created_by'] == $currentUserId && !empty($r['comment'])) {
            $hasSubmittedComment = true;
            break;
        }
    }

    // Filter: hide other members' comment/managed_by if current user hasn't submitted theirs
    $result = [];
    foreach ($all as $r) {
        if ($r['created_by'] != $currentUserId && !$hasSubmittedComment) {
            $r['comment']    = '—';
            $r['managed_by'] = '—';
        }
        $result[] = $r;
    }
    return $result;
}

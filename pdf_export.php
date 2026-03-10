<?php
/**
 * PDF Export - Admin Only
 * Uses Dompdf to generate PDF reports.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAdmin();

// Check if Dompdf is available
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die('Dompdf is not installed. Run: composer require dompdf/dompdf');
}
require_once $autoloadPath;

use Dompdf\Dompdf;
use Dompdf\Options;

$filterDate  = $_GET['date'] ?? date('Y-m-d');
$filterMonth = $_GET['month'] ?? '';
$viewMode    = $_GET['view'] ?? 'day';

if ($viewMode === 'month' && !empty($filterMonth)) {
    $requisitions = getAllRequisitionsByMonth($filterMonth);
    $displayLabel = date('F Y', strtotime($filterMonth . '-01'));
    $filename = 'Badhan_Report_' . $filterMonth . '.pdf';
} else {
    $requisitions = getAllRequisitionsByDate($filterDate);
    $displayLabel = date('d M Y', strtotime($filterDate));
    $filename = 'Badhan_Report_' . $filterDate . '.pdf';
}

$stats = calculateBloodGroupStats($requisitions);
$totalManaged = array_sum(array_column($stats, 'managed'));
$totalReqs    = count($requisitions);

// Build HTML for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; margin: 20px; }
    h1 { text-align: center; color: #c0392b; font-size: 18px; margin-bottom: 2px; }
    h2 { text-align: center; color: #555; font-size: 12px; margin-bottom: 15px; }
    .stats { margin-bottom: 15px; text-align: center; }
    .stat-item { display: inline-block; background: #f0f0f0; padding: 3px 10px; border-radius: 12px; 
                 margin: 2px; font-size: 9px; font-weight: bold; }
    .stat-managed { color: #27ae60; }
    table { width: 100%; border-collapse: collapse; font-size: 9px; margin-top: 10px; }
    th { background: #2c3e50; color: white; padding: 6px 4px; text-align: left; }
    td { padding: 5px 4px; border-bottom: 1px solid #ddd; }
    tr:nth-child(even) { background: #f9f9f9; }
    .managed { color: #27ae60; font-weight: bold; }
    .referred { color: #f39c12; font-weight: bold; }
    .others { color: #3498db; font-weight: bold; }
    .pending { color: #999; font-style: italic; }
    .summary { margin-top: 15px; padding: 10px; background: #f5f5f5; border-radius: 5px; }
    .summary td { border: none; padding: 3px 8px; font-size: 10px; }
    .footer { text-align: center; margin-top: 20px; font-size: 8px; color: #999; }
</style>
</head>
<body>
<h1>🩸 Badhan DU Zone</h1>
<h2>Blood Requisition Report — ' . htmlspecialchars($displayLabel) . '</h2>

<div class="stats">';

foreach ($stats as $bg => $s) {
    if ($s['total'] > 0) {
        $html .= '<span class="stat-item">' . $bg . ' (<span class="stat-managed">' . $s['managed'] . '</span>/' . $s['total'] . ')</span> ';
    }
}
$html .= '<span class="stat-item">Total (<span class="stat-managed">' . $totalManaged . '</span>/' . $totalReqs . ')</span>';
$html .= '</div>';

if (empty($requisitions)) {
    $html .= '<p style="text-align:center; color:#999;">No requisitions found for the selected period.</p>';
} else {
    $html .= '
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
                <th>Address</th>
                <th>Contact</th>
                <th>Comment</th>
                <th>Managed By</th>
                <th>By</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($requisitions as $i => $r) {
        $commentClass = '';
        $commentText = 'Pending';
        if ($r['comment'] === 'Managed') { $commentClass = 'managed'; $commentText = 'Managed'; }
        elseif ($r['comment'] === 'Referred') { $commentClass = 'referred'; $commentText = 'Referred'; }
        elseif ($r['comment'] === 'Others') { $commentClass = 'others'; $commentText = 'Others'; }
        else { $commentClass = 'pending'; }

        $html .= '<tr>
            <td>' . ($i + 1) . '</td>
            <td>' . date('d M h:i A', strtotime($r['created_at'])) . '</td>
            <td>' . htmlspecialchars($r['patient_name']) . '</td>
            <td>' . $r['patient_age'] . '</td>
            <td><strong>' . htmlspecialchars($r['blood_group']) . '</strong></td>
            <td>' . $r['quantity'] . '</td>
            <td>' . htmlspecialchars($r['component']) . '</td>
            <td>' . htmlspecialchars($r['hospital_name']) . '</td>
            <td>' . htmlspecialchars(mb_strimwidth($r['problem'], 0, 30, '...')) . '</td>
            <td>' . htmlspecialchars($r['attendant_name']) . '</td>
            <td>' . htmlspecialchars($r['attendant_address'] ?? '') . '</td>
            <td>' . htmlspecialchars($r['attendant_contact']) . '</td>
            <td class="' . $commentClass . '">' . $commentText . '</td>
            <td>' . htmlspecialchars($r['managed_by'] ?: '—') . '</td>
            <td>' . htmlspecialchars($r['creator_name']) . '</td>
        </tr>';
    }

    $html .= '</tbody></table>';

    // Summary
    $html .= '
    <table class="summary">
        <tr>
            <td><strong>Total Requisitions:</strong></td><td>' . $totalReqs . '</td>
            <td><strong>Managed:</strong></td><td class="managed">' . $totalManaged . '</td>
            <td><strong>Referred:</strong></td><td class="referred">' . count(array_filter($requisitions, fn($r) => $r['comment'] === 'Referred')) . '</td>
            <td><strong>Pending:</strong></td><td class="pending">' . count(array_filter($requisitions, fn($r) => empty($r['comment']))) . '</td>
        </tr>
    </table>';
}

$html .= '
<div class="footer">
    Generated on ' . date('d M Y, h:i A') . ' | Badhan DU Zone - Blood Requisition Management System
</div>
</body>
</html>';

// Generate PDF
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Stream PDF to browser
$dompdf->stream($filename, ['Attachment' => true]);
exit;

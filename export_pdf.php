<?php
require_once __DIR__ . '/lib/auth.php';
require_any_role(['doctor','admin']);
require_once __DIR__ . '/lib/SimplePDF.php';

$pid = intval($_GET['patient_id'] ?? 0);
$pat = $pdo->prepare("SELECT id,name,email FROM users WHERE id=? AND role='patient'");
$pat->execute([$pid]);
$patient = $pat->fetch();
if (!$patient) { exit('Patient not found'); }

$stmt = $pdo->prepare("SELECT a.*, d.name AS doctor_name
                       FROM appointments a
                       JOIN users d ON d.id=a.doctor_id
                       WHERE a.patient_id=?
                       ORDER BY a.scheduled_at DESC");
$stmt->execute([$pid]);
$rows = $stmt->fetchAll();

$pdf = new SimplePDF("Medical history for: " . $patient['name']);
$pdf->addLine("Generated: " . date('Y-m-d H:i'));
$pdf->addLine("Patient email: " . $patient['email']);
$pdf->addLine(str_repeat('-', 60));
if ($rows) {
    foreach ($rows as $r) {
        $pdf->addLine(date('Y-m-d H:i', strtotime($r['scheduled_at'])) . " | Dr. " . $r['doctor_name'] . " | " . $r['status']);
        if (!empty($r['notes'])) {
            $notes = trim(preg_replace('/\s+/', ' ', $r['notes']));
            $pdf->addLine("  Notes: " . $notes);
        }
    }
} else {
    $pdf->addLine("No past appointments.");
}
$pdf->output("history_{$patient['id']}.pdf");

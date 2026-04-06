<?php
/**
 * API: Get diagnoses for a patient.
 * GET /api/get-diagnoses?patient_id=<id>
 * Returns JSON array of diagnosis records.
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../Connect.php';

$patientId = (int) ($_GET['patient_id'] ?? 0);
if ($patientId <= 0) {
    echo json_encode([]);
    exit;
}

$conn = \Connect::getInstance()->getConnection();
$stmt = $conn->prepare("SELECT id, `condition`, severity, notes, created_at FROM view_active_diagnoses WHERE patient_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $patientId);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode($rows);

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

$patientId = (int) ($_GET['patient_id'] ?? 0);
if ($patientId <= 0) {
    echo json_encode([]);
    exit;
}

require_once __DIR__ . '/../../Connect.php';

$conn = \Connect::getInstance()->getConnection();
$stmt = $conn->prepare("SELECT my_diagnosis(?) AS result");
$stmt->bind_param('i', $patientId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$diagnoses = ($row && $row['result']) ? json_decode($row['result'], true) : [];
echo json_encode($diagnoses ?? []);

<?php
/**
 * API: Check drug interaction between two medicines.
 * GET /api/check-interaction?m1=<id>&m2=<id>
 * Returns JSON: {severity, description, recommendation} or {} if no interaction.
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../Connect.php';

$m1 = (int) ($_GET['m1'] ?? 0);
$m2 = (int) ($_GET['m2'] ?? 0);

if ($m1 <= 0 || $m2 <= 0) {
    echo json_encode(['error' => 'Invalid medicine IDs']);
    exit;
}

$conn = \Connect::getInstance()->getConnection();
$stmt = $conn->prepare("SELECT check_drug_interactions(?, ?) AS result");
$stmt->bind_param('ii', $m1, $m2);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || !$row['result']) {
    echo json_encode([]);
    exit;
}

$decoded = json_decode($row['result'], true);
echo json_encode((is_array($decoded) && !empty($decoded)) ? $decoded : []);

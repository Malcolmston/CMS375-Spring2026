<?php
/**
 * API: Search medicines by name.
 * GET /api/search-medicine?q=<term>
 * Returns JSON array from search_medicine() SQL function.
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../Connect.php';

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

$conn = \Connect::getInstance()->getConnection();
$stmt = $conn->prepare("SELECT search_medicine(?) AS result");
$stmt->bind_param('s', $q);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$data = ($row && $row['result']) ? json_decode($row['result'], true) : [];
echo json_encode($data ?? []);

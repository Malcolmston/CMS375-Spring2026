<?php
/**
 * API: Search patients by name.
 * GET /api/search-patient?q=<term>
 * Returns JSON array of {id, firstname, lastname, age, blood, gender}.
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../account/Account.php';

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

$rows = \account\Account::searchPatients($q, 20);

echo json_encode($rows);

<?php
/**
 * GET /api/nearest_institutions
 *
 * Returns the nearest (up to) 100 institutions to the authenticated patient
 * as a JSON array compatible with map.php's PUT format:
 *   [{ "point":[lat,lng], "name":"...", "address":"...", "description":"...", "phone":"..." }, ...]
 *
 * Query params (optional fallback if patient has no stored location):
 *   ?lat=28.5383&lng=-81.3792
 */

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../Connect.php';
require_once __DIR__ . '/../../account/Account.php';

use account\Account;

$user_id = (int) $_SESSION['user_id'];

// ── Resolve patient location ───────────────────────────────────────────────
$patLat = null;
$patLng = null;

$patient = Account::getUserById($user_id);
if ($patient) {
    $loc    = $patient->getLocation();
    // MySQL SRID-4326 POINT(x y) => x=longitude, y=latitude
    $patLat = (float) $loc->y;
    $patLng = (float) $loc->x;
}

// Allow override / fallback from query string
if (isset($_GET['lat'], $_GET['lng'])) {
    $patLat = (float) $_GET['lat'];
    $patLng = (float) $_GET['lng'];
}

if ($patLat === null || $patLng === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Patient location unavailable. Pass ?lat=&lng=']);
    exit;
}

// ── Query nearest 100 institutions with coordinates ────────────────────────
$db   = (new Connect())->getConnection();
$sql  = "
    SELECT
        id,
        name,
        institution_type,
        phone,
        address,
        loc_x,
        loc_y,
        /* Haversine distance in km */
        (6371 * ACOS(
            COS(RADIANS(?)) * COS(RADIANS(loc_x)) *
            COS(RADIANS(loc_y) - RADIANS(?)) +
            SIN(RADIANS(?)) * SIN(RADIANS(loc_x))
        )) AS distance_km
    FROM institution
    WHERE deleted_at IS NULL
      AND loc_x IS NOT NULL
      AND loc_y IS NOT NULL
    ORDER BY distance_km ASC
    LIMIT 100
";

$stmt = $db->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Query prepare failed']);
    exit;
}

$stmt->bind_param('ddd', $patLat, $patLng, $patLat);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Format for map.php ─────────────────────────────────────────────────────
$typeLabels = [
    'HOSPITAL'     => 'Hospital',
    'CLINIC'       => 'Clinic',
    'URGENT_CARE'  => 'Urgent Care',
    'PHARMACY'     => 'Pharmacy',
    'LAB'          => 'Laboratory',
    'OTHER'        => 'Health Facility',
];

$result = array_map(function ($row) use ($typeLabels) {
    $typeLabel = $typeLabels[$row['institution_type']] ?? 'Health Facility';
    return [
        'point'       => [(float) $row['loc_x'], (float) $row['loc_y']],
        'name'        => $row['name'],
        'address'     => $row['address'] ?? '',
        'description' => $typeLabel . (isset($row['distance_km'])
            ? sprintf(' · %.1f km away', $row['distance_km'])
            : ''),
        'phone'       => $row['phone'] ?? '',
        'type'        => $row['institution_type'],
        'id'          => (int) $row['id'],
    ];
}, $rows);

echo json_encode($result);

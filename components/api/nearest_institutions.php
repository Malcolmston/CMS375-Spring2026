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

require_once __DIR__ . '/../../account/Account.php';
require_once __DIR__ . '/../../account/Patient.php';
require_once __DIR__ . '/../../account/Physician.php';
require_once __DIR__ . '/../../account/Nurse.php';
require_once __DIR__ . '/../../account/Pharmacist.php';
require_once __DIR__ . '/../../account/Admin.php';
require_once __DIR__ . '/../../account/LabTech.php';
require_once __DIR__ . '/../../account/Billing.php';
require_once __DIR__ . '/../../account/Surgeon.php';
require_once __DIR__ . '/../../account/Radiologist.php';
require_once __DIR__ . '/../../account/Receptionist.php';
require_once __DIR__ . '/../../account/Ems.php';
require_once __DIR__ . '/../../account/Therapist.php';

use account\Account;

$user_id = (int) $_SESSION['user_id'];

// Resolve patient location
$patLat = null;
$patLng = null;

// Look up user role first, then instantiate correct class
$roleMap = [
    'PATIENT'      => \account\Patient::class,
    'PHYSICIAN'    => \account\Physician::class,
    'NURSE'        => \account\Nurse::class,
    'PHARMACIST'   => \account\Pharmacist::class,
    'ADMIN'        => \account\Admin::class,
    'LAB_TECH'     => \account\LabTech::class,
    'BILLING'      => \account\Billing::class,
    'SURGEON'      => \account\Surgeon::class,
    'RADIOLOGIST'  => \account\Radiologist::class,
    'RECEPTIONIST' => \account\Receptionist::class,
    'EMS'          => \account\Ems::class,
    'THERAPIST'    => \account\Therapist::class,
];

// Get user role from Account class
$userRole = Account::getUserRole($user_id);

$userClass = $roleMap[$userRole] ?? \account\Patient::class;
$patient = $userClass::getUserById($user_id);

if ($patient) {
    $loc    = $patient->getLocation();
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

// Get nearest institutions using Account class method
$rows = Account::getNearestInstitutions($patLat, $patLng, 100);

// Format for map.php
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
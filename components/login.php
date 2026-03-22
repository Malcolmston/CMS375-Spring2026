<?php

require_once __DIR__ . '/../Connect.php';
require_once __DIR__ . '/../account/Patient.php';
require_once __DIR__ . '/../account/Admin.php';
require_once __DIR__ . '/../account/Billing.php';
require_once __DIR__ . '/../account/LabTech.php';

use account\Patient;
use account\Admin;
use account\Billing;
use account\LabTech;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

session_start();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

match ($uri) {
    '/login', '/patient/login' => handle_patient(),
    '/staff/login'             => handle_staff(),
    '/admin/login'             => handle_admin(),
    default                    => abort(404),
};

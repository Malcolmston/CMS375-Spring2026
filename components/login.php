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

function handle_patient(): void
{
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        redirect_back('Missing credentials.');
    }

    $patient = new Patient();
    if (!$patient->login($email, $password)) {
        redirect_back('Invalid email or password.');
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $patient->id;
    $_SESSION['role']    = 'PATIENT';
    header('Location: /patient/dashboard');
    exit;
}

function handle_staff(): void
{
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $employid = trim($_POST['employid'] ?? '');

    if (!$email || !$password || !$employid) {
        redirect_back('Missing credentials.');
    }

    $role = resolve_staff_role($email, $employid);
    if ($role === null) {
        redirect_back('Invalid credentials.');
    }

    $account = match ($role) {
        'BILLING'  => new Billing(),
        'LAB_TECH' => new LabTech(),
        default    => null,
    };

    if ($account === null || !$account->loginWithId($email, $password, $employid)) {
        redirect_back('Invalid credentials.');
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $account->id;
    $_SESSION['role']    = $role;
    header('Location: /staff/dashboard');
    exit;
}

function handle_admin(): void
{
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $adminid  = trim($_POST['adminid']  ?? '');

    if (!$email || !$password || !$adminid) {
        redirect_back('Missing credentials.');
    }

    $admin = new Admin();
    if (!$admin->loginWithId($email, $password, $adminid)) {
        redirect_back('Invalid credentials.');
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $admin->id;
    $_SESSION['role']    = 'ADMIN';
    header('Location: /admin/dashboard');
    exit;
}

/**
 * Looks up the role for a staff member by email and employid
 * so the correct account class can be instantiated.
 */
function resolve_staff_role(string $email, string $employid): ?string
{
    $sql  = "SELECT role FROM view_user_role_pwd
             WHERE email    = ?
               AND employid = ?
             LIMIT 1";

    $conn = (new Connect())->getConnection();
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $email, $employid);
    $stmt->execute();
    $stmt->bind_result($role);
    $found = $stmt->fetch();
    $stmt->close();

    return $found ? $role : null;
}

function redirect_back(string $error): never
{
    $_SESSION['login_error'] = $error;
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
    exit;
}

function abort(int $code): never
{
    http_response_code($code);
    exit;
}

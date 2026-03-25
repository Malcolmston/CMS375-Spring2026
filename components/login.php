<?php

require_once __DIR__ . '/../account/Patient.php';
require_once __DIR__ . '/../account/Admin.php';
require_once __DIR__ . '/../account/Billing.php';
require_once __DIR__ . '/../account/LabTech.php';

use account\Patient;
use account\Admin;
use account\Billing;
use account\LabTech;
use account\role;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => 'localhost',
    'secure' => true,      // ONLY over HTTPS
    'httponly' => true,    // JS cannot access cookie
    'samesite' => 'Strict' // or 'Lax'
]);


session_start();

$route = $_POST['route'] ?? '';

$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
$_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

if (!isset($_SESSION['ip']) || !isset($_SESSION['user_agent'])) {
    abort(400, 'Session initialization failed');
}

$timeout = 1800; // 30 minutes

if (isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset();
    session_destroy();
}

$_SESSION['last_activity'] = time();

if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 300) { // every 5 min
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

match ($route) {
    'patient' => handle_patient(),
    'staff'   => handle_staff(),
    'admin'   => handle_admin(),
    default   => abort(404),
};

/**
 * Handles the authentication and session initialization for a patient.
 *
 * This method processes login credentials submitted via a POST request.
 * It validates the email and password, attempts to authenticate the patient,
 * and sets up the session upon successful login. If authentication fails
 * or credentials are missing, the user is redirected back with an appropriate error message.
 *
 * @return void
 */
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
    $_SESSION['user_id'] = $patient->getId();
    $_SESSION['role']    = 'PATIENT';
    header('Location: dashboard');
    exit;
}

/**
 * Handles staff authentication by verifying login credentials and determining the role of the user.
 * If authentication is successful, initiates a session and redirects to the staff dashboard.
 * If authentication fails, redirects back with an appropriate error message.
 *
 * @return void
 */
function handle_staff(): void
{
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $employid = trim($_POST['employid'] ?? '');

    if (!$email || !$password || !$employid) {
        redirect_back('Missing credentials.');
    }

    $role = Billing::resolveRole($email, $employid);
    if ($role === null) {
        redirect_back('Invalid credentials.');
    }

    $account = match ($role) {
        Role::BILLING->value => new Billing(),
        Role::LAB_TECH->value => new LabTech(),
        default    => null,
    };

    if ($account === null || !$account->loginWithId($email, $password, $employid)) {
        redirect_back('Invalid credentials.');
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $account->getId();
    $_SESSION['role']    = $role;
    header('Location: dashboard');
    exit;
}

/**
 * Handles the administrative login process by validating and authenticating the provided credentials.
 *
 * @return void
 */
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
    $_SESSION['user_id'] = $admin->getId();
    $_SESSION['role']    = 'ADMIN';
    header('Location: dashboard');
    exit;
}

/**
 * Redirects the user back to the referring page and sets a session error message.
 *
 * @param string $error The error message to be stored in the session.
 * @return never This method does not return a value as it terminates script execution with exit().
 */
function redirect_back(string $error): never
{
    $_SESSION['login_error'] = $error;
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
    exit;
}

/**
 * Terminates the script execution and sends the specified HTTP response code.
 *
 * @param int $code The HTTP response code to be sent before exiting.
 * @return never This method does not return a value as it terminates the script using exit.
 */
function abort(int $code): never
{
    http_response_code($code);
    exit;
}

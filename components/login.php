<?php

// Unified login handler - serves HTML forms for GET, processes auth for POST
require_once __DIR__ . '/../account/Patient.php';
require_once __DIR__ . '/../account/Admin.php';
require_once __DIR__ . '/../account/Billing.php';
require_once __DIR__ . '/../account/LabTech.php';
require_once __DIR__ . '/../account/Pharmacist.php';
require_once __DIR__ . '/../account/Radiologist.php';
require_once __DIR__ . '/../account/Receptionist.php';
require_once __DIR__ . '/../account/Surgeon.php';
require_once __DIR__ . '/../account/Ems.php';
require_once __DIR__ . '/../account/Therapist.php';
require_once __DIR__ . '/../account/Physician.php';
require_once __DIR__ . '/../account/Nurse.php';

use account\Ems;
use account\Nurse;
use account\Patient;
use account\Admin;
use account\Billing;
use account\LabTech;
use account\Pharmacist;
use account\Physician;
use account\Radiologist;
use account\Receptionist;
use account\role;
use account\Surgeon;
use account\Therapist;

// GET request - show login form
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $type = $_GET['type'] ?? 'patient';

    // Validate type
    if (!in_array($type, ['patient', 'staff', 'admin'])) {
        $type = 'patient';
    }

    $formConfig = match ($type) {
        'staff' => [
            'title' => 'Staff Login',
            'subtitle' => 'Sign in with your employee credentials',
            'route' => 'staff',
            'focusRing' => 'focus:ring-blue-500',
            'buttonBg' => 'bg-blue-600 hover:bg-blue-700',
            'iconBg' => 'bg-blue-50',
            'iconColor' => 'text-blue-600',
            'extraFields' => '<div>
                <label for="employid" class="block text-sm font-medium text-gray-700 mb-1">Employee ID</label>
                <input
                    type="text"
                    id="employid"
                    name="employid"
                    required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="e.g., a1b2c3d4-2ec7-11ef-..."
                >
            </div>',
        ],
        'admin' => [
            'title' => 'Admin Login',
            'subtitle' => 'Restricted access — authorised personnel only',
            'route' => 'admin',
            'focusRing' => 'focus:ring-red-500',
            'buttonBg' => 'bg-red-600 hover:bg-red-700',
            'iconBg' => 'bg-red-50',
            'iconColor' => 'text-red-600',
            'extraFields' => '<div>
                <label for="adminid" class="block text-sm font-medium text-gray-700 mb-1">Admin ID</label>
                <input
                    type="text"
                    id="adminid"
                    name="adminid"
                    required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                    placeholder="ADM-XXXXXX"
                >
            </div>',
        ],
        default => [
            'title' => 'Patient Login',
            'subtitle' => 'Access your health records and appointments',
            'route' => 'patient',
            'focusRing' => 'focus:ring-blue-500',
            'buttonBg' => 'bg-slate-800 hover:bg-slate-700',
            'iconBg' => 'bg-slate-100',
            'iconColor' => 'text-slate-700',
            'extraFields' => '',
        ],
    };
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($formConfig['title']) ?> | MedHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/1ad3aa32da.js" crossorigin="anonymous"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            min-height: 100vh;
        }
        .login-card {
            background: white;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Header with Logo -->
    <header class="pt-8 pb-4 px-6">
        <div class="max-w-md mx-auto">
            <a href="/" class="inline-flex items-center gap-2 text-xl font-serif font-semibold italic text-slate-800 tracking-tight hover:opacity-80 transition-opacity">
                <i class="fa-solid fa-heart-pulse text-rose-500"></i>
                medhealth
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center px-4 pb-12">
        <div class="login-card rounded-2xl w-full max-w-md p-8">
            <!-- Icon -->
            <div class="text-center mb-6">
                <div class="w-14 h-14 <?= htmlspecialchars($formConfig['iconBg']) ?> rounded-full flex items-center justify-center mx-auto mb-4">
                    <?php if ($type === 'admin'): ?>
                        <i class="fa-solid fa-shield-halved <?= htmlspecialchars($formConfig['iconColor']) ?> text-xl"></i>
                    <?php elseif ($type === 'staff'): ?>
                        <i class="fa-solid fa-id-card <?= htmlspecialchars($formConfig['iconColor']) ?> text-xl"></i>
                    <?php else: ?>
                        <i class="fa-solid fa-user <?= htmlspecialchars($formConfig['iconColor']) ?> text-xl"></i>
                    <?php endif; ?>
                </div>
                <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($formConfig['title']) ?></h1>
                <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($formConfig['subtitle']) ?></p>
            </div>

            <form method="POST" action="/login" class="space-y-5">
                <input type="hidden" name="route" value="<?= htmlspecialchars($formConfig['route']) ?>">

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-regular fa-envelope text-gray-400"></i>
                        </div>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            required
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 <?= htmlspecialchars($formConfig['focusRing']) ?> focus:border-transparent transition-all"
                            placeholder="you@example.com"
                        >
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-lock text-gray-400"></i>
                        </div>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 <?= htmlspecialchars($formConfig['focusRing']) ?> focus:border-transparent transition-all"
                            placeholder="••••••••"
                        >
                    </div>
                </div>

                <?= $formConfig['extraFields'] ?>

                <button
                    type="submit"
                    class="w-full <?= htmlspecialchars($formConfig['buttonBg']) ?> text-white font-semibold rounded-lg px-4 py-2.5 text-sm transition-all hover:shadow-lg hover:-translate-y-0.5"
                >
                    Sign In
                </button>
            </form>

            <!-- Footer Links -->
            <div class="mt-6 pt-6 border-t border-gray-100 text-center">
                <?php if ($type === 'patient'): ?>
                    <p class="text-sm text-gray-500">
                        Don't have an account?
                        <a href="/register" class="text-slate-700 font-medium hover:underline">Register</a>
                    </p>
                <?php endif; ?>
                <a href="/" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mt-3">
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to Home
                </a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include __DIR__ . '/home/footer.php'; ?>

    <!-- Login error popup -->
    <div id="error-popup" class="hidden fixed inset-0 z-50 flex items-start justify-center pt-6 px-4 pointer-events-none">
        <div class="pointer-events-auto flex items-start gap-3 bg-red-50 border border-red-200 text-red-800 rounded-xl shadow-lg px-5 py-4 max-w-sm w-full">
            <svg class="mt-0.5 shrink-0 w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <span id="error-message" class="text-sm font-medium flex-1"></span>
            <button onclick="document.getElementById('error-popup').classList.add('hidden')" class="text-red-400 hover:text-red-600 text-lg leading-none">&times;</button>
        </div>
    </div>
    <script>
        const params = new URLSearchParams(location.search);
        const err = params.get('error');
        if (err) {
            document.getElementById('error-message').textContent = err;
            document.getElementById('error-popup').classList.remove('hidden');
            history.replaceState(null, '', location.pathname);
        }
    </script>
</body>
</html>
    <?php
    exit;
}

// POST request - process login
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => true,      // ONLY over HTTPS
    'httponly' => true,    // JS cannot access cookie
    'samesite' => 'Strict'
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
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
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

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    $_SESSION['user_id'] = $patient->getId();
    $_SESSION['role']    = 'PATIENT';
    session_write_close();
    header('Location: /dashboard');
    exit;
}

/**
 * Handles staff authentication by verifying login credentials and determining the role of the user.
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
        role::PHYSICIAN->value => new Physician(),
        role::NURSE->value => new Nurse(),
        role::PHARMACIST->value => new Pharmacist(),
        role::RADIOLOGIST->value => new Radiologist(),
        role::LAB_TECH->value => new LabTech(),
        role::SURGEON->value => new Surgeon(),
        role::RECEPTIONIST->value => new Receptionist(),
        role::BILLING->value => new Billing(),
        role::EMS->value => new EMS(),
        role::THERAPIST->value => new Therapist(),
        default    => null,
    };

    if ($account === null || !$account->loginWithId($email, $password, $employid)) {
        redirect_back('Invalid credentials.');
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    $_SESSION['user_id'] = $account->getId();
    $_SESSION['role']    = $role;
    session_write_close();
    header('Location: /dashboard');
    exit;
}

/**
 * Handles the administrative login process by validating and authenticating the provided credentials.
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

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    $_SESSION['user_id'] = $admin->getId();
    $_SESSION['role']    = 'ADMIN';
    session_write_close();
    header('Location: /dashboard');
    exit;
}

/**
 * Redirects the user back to the referring page and sets a session error message.
 */
function redirect_back(string $error): never
{
    $base = $_SERVER['HTTP_REFERER'] ?? '/';
    $url  = strtok($base, '?') . '?error=' . urlencode($error);
    header('Location: ' . $url);
    exit;
}

/**
 * Terminates the script execution and sends the specified HTTP response code.
 */
function abort(int $code): never
{
    http_response_code($code);
    exit;
}
<?php
require_once __DIR__ . '/../../../account/role.php';
require_once __DIR__ . '/../../../account/Account.php';
require_once __DIR__ . '/../../../account/Receptionist.php';

use account\role;

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /index');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$roleValue = $_SESSION['role'];
$staffRole = role::tryFrom($roleValue);

if ($staffRole === null || $staffRole !== role::RECEPTIONIST) {
    header('Location: /index');
    exit;
}

$staff = \account\Receptionist::getUserById($user_id);

if (!$staff) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Unable to load profile.'];
    header('Location: /index');
    exit;
}

require_once __DIR__ . '/../utils/staff_post.php';
handle_staff_post($staff, '/dashboard/staff/receptionist');

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

require_once __DIR__ . '/base.php';
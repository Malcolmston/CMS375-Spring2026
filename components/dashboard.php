<?php

require_once __DIR__ . '/../account/role.php';
use account\role;

session_start();

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: /index');
    exit;
}

if (!role::isValid($_SESSION['role'])) {
    header('Location: /index');
    exit;
}

$role = role::from($_SESSION['role']);

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$destination = match ($role) {
    role::PATIENT => '/dashboard/patient',
    role::ADMIN   => '/dashboard/admin',
    default       => '/dashboard/staff',
};

header('Location: ' . $destination);
exit;
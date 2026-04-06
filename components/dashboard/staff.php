<?php
require_once __DIR__ . '/../../account/role.php';

use account\role;

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: /index');
    exit;
}

$user_role = $_SESSION['role'];
$staffRole = role::tryFrom($user_role);

if ($staffRole === null) {
    header('Location: /index');
    exit;
}

$destination = match ($staffRole) {
    role::PHYSICIAN    => '/dashboard/staff/physician',
    role::NURSE        => '/dashboard/staff/nurse',
    role::PHARMACIST   => '/dashboard/staff/pharmacist',
    role::RECEPTIONIST => '/dashboard/staff/receptionist',
    role::LAB_TECH     => '/dashboard/staff/labtech',
    role::RADIOLOGIST  => '/dashboard/staff/radiologist',
    role::SURGEON      => '/dashboard/staff/surgeon',
    role::THERAPIST    => '/dashboard/staff/therapist',
    role::BILLING      => '/dashboard/staff/billing',
    role::EMS          => '/dashboard/staff/ems',
    default            => '/index',
};

header('Location: ' . $destination);
exit;
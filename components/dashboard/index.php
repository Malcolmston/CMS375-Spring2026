<?php

require_once __DIR__ . '/../../account/role.php';

use account\role;

session_start();


$user_id = $role = null;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
}

if (!$user_id || !$role) {
    header('Location: index.php');
    exit;
}

if (!Role::isValid($role)) {
    header('Location: index.php');
    exit;
}
?>

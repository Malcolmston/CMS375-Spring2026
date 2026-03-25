<?php

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || $token !== ($_SESSION['csrf_token'] ?? '')) {
        header('Location: /index');
        exit;
    }

    // Destroy all session data
    $_SESSION = array();

    // Destroy the session
    session_destroy();

    // Redirect to home page
    header('Location: /index');
    exit;
}

// If not POST, redirect to home
header('Location: /index');
exit;

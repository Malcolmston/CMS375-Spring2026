<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();

    // Destroy all session data
    $_SESSION = array();

    // Destroy the session
    session_destroy();

    // Redirect to home page
    header('Location: index.php');
    exit;
}

// If not POST, redirect to home
header('Location: index.php');
exit;
<?php

session_start();

// Destroy all session data
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to home page
header('Location: /index');
exit;
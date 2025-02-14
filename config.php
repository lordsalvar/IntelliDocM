<?php
// config.php

// Start a secure session with strict settings
session_start([
    'cookie_lifetime' => 3600,           // 1 hour
    'cookie_httponly' => true,           // JavaScript cannot access session cookie
    'cookie_secure' => isset($_SERVER['HTTPS']), // Only send cookie over HTTPS if available
    'use_strict_mode' => true,           // Strict session handling
]);

// Prevent session fixation
session_regenerate_id(true);

// Create a CSRF token if one does not exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include the database connection
include_once 'database.php';

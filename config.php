<?php
// config.php

// Check if a session hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 3600,           // 1 hour
        'cookie_httponly' => true,           // JavaScript cannot access session cookie
        'cookie_secure' => isset($_SERVER['HTTPS']), // Only send cookie over HTTPS if available
        'use_strict_mode' => true,           // Strict session handling
    ]);
}

// Prevent session fixation
if (!isset($_SESSION['initialized'])) {
    session_regenerate_id(true);
    $_SESSION['initialized'] = true;
}

// Create a CSRF token if one does not exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include the database connection
require_once 'database.php';

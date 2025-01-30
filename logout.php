<?php
session_start();
include 'system_log/activity_log.php';

$username = $_SESSION['username'];
$userActivity = 'User logged out';  // Example activity description

// Log the activity
logActivity($username, $userActivity);
session_start();
session_destroy();
header('Location: login.php');
exit();

<?php
session_start();
include '../system_log/activity_log.php';  // Include your activity logging function

// Check if user is logged in
if (isset($_SESSION['username'])) {
    // Retrieve the activity from the POST request
    if (isset($_POST['activity'])) {
        $username = $_SESSION['username'];  // Get the logged-in username
        $userActivity = $_POST['activity'];  // Get the activity description

        // Call the function to log the activity
        logActivity($username, $userActivity);
    }
} else {
    echo "User not logged in.";
}

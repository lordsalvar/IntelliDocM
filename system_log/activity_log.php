<?php
function logActivity($username, $userActivity)
{
    // Get the IP address of the user
    $ipAddress = $_SERVER['REMOTE_ADDR'];

    // Database connection (replace with your actual credentials)
    $dbHost = 'localhost';
    $dbName = 'dbcb';
    $dbUser = 'root';
    $dbPass = '';

    try {
        // Create a PDO connection
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare the SQL statement
        $stmt = $pdo->prepare("INSERT INTO activity_log (username, ip_address, user_activity) 
                               VALUES (:username, :ip_address, :user_activity)");

        // Bind the parameters
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':ip_address', $ipAddress);
        $stmt->bindParam(':user_activity', $userActivity);

        // Execute the query
        $stmt->execute();
    } catch (PDOException $e) {
        // Handle the exception (if any)
        echo "Error: " . $e->getMessage();
    }
}

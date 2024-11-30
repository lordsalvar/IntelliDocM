<?php
require_once 'database.php';

// Database connection (Assuming a MySQL database)

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['event_id'])) {
        $eventId = intval($_POST['event_id']);

        // Delete event from the database
        $sql = "DELETE FROM events WHERE event_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $eventId);
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Event deleted successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to delete event. Please try again."]);
            }
            $stmt->close();
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to prepare the SQL statement."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid event ID."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}

// Close the database connection
$conn->close();

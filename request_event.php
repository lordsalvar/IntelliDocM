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
    // Debugging statement to log incoming POST request data
    error_log("Received POST request with the following data:");
    error_log("Event Title: " . (isset($_POST['event_title']) ? $_POST['event_title'] : "MISSING"));
    error_log("Event Description: " . (isset($_POST['event_description']) ? $_POST['event_description'] : "MISSING"));
    error_log("Event Start Date: " . (isset($_POST['event_start_date']) ? $_POST['event_start_date'] : "MISSING"));
    error_log("Event End Date: " . (isset($_POST['event_end_date']) ? $_POST['event_end_date'] : "MISSING"));

    if (isset($_POST['event_title'], $_POST['event_description'], $_POST['event_start_date'], $_POST['event_end_date'])) {
        $eventTitle = $_POST['event_title'];
        $eventDescription = $_POST['event_description'];
        $eventStartDate = $_POST['event_start_date'];
        $eventEndDate = $_POST['event_end_date'];

        // Check if the requested dates are already blocked
        $checkSql = "SELECT * FROM events WHERE event_start_date <= ? AND event_end_date >= ?";
        $stmt = $conn->prepare($checkSql);
        if ($stmt) {
            $stmt->bind_param("ss", $eventEndDate, $eventStartDate);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo json_encode(["status" => "error", "message" => "The requested dates are already blocked."]);
            } else {
                // Add the event to the database
                $insertSql = "INSERT INTO events (event_title, event_description, event_start_date, event_end_date) VALUES (?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertSql);
                if ($insertStmt) {
                    $insertStmt->bind_param("ssss", $eventTitle, $eventDescription, $eventStartDate, $eventEndDate);
                    if ($insertStmt->execute()) {
                        echo json_encode(["status" => "success", "message" => "Event request submitted successfully."]);
                    } else {
                        echo json_encode(["status" => "error", "message" => "Failed to submit event request. Please try again."]);
                    }
                    $insertStmt->close();
                } else {
                    echo json_encode(["status" => "error", "message" => "Failed to prepare the SQL statement for insertion."]);
                }
            }
            $stmt->close();
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to prepare the SQL statement for checking."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Missing event details."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}

// Close the database connection
$conn->close();

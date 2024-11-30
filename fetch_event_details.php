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
    if (isset($_POST['event_date'])) {
        $eventDate = $_POST['event_date'];

        // Fetch all events for the given date
        $sql = "SELECT event_title, event_description, event_start_date, event_end_date FROM events WHERE ? BETWEEN event_start_date AND event_end_date";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $eventDate);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $events = [];
                while ($row = $result->fetch_assoc()) {
                    $events[] = [
                        "event_title" => $row['event_title'],
                        "event_description" => $row['event_description'],
                        "event_start_date" => $row['event_start_date'],
                        "event_end_date" => $row['event_end_date']
                    ];
                }
                echo json_encode(["status" => "success", "events" => $events]);
            } else {
                echo json_encode(["status" => "error", "message" => "No events found for the selected date."]);
            }
            $stmt->close();
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to prepare the SQL statement."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Missing event date."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}

// Close the database connection
$conn->close();

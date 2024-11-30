<?php
require_once 'database.php';

// Check if the required POST parameters are set
if (isset($_POST['event_title'], $_POST['event_description'], $_POST['event_start_date'], $_POST['event_end_date'])) {
    // Retrieve the POST data
    $eventTitle = $_POST['event_title'];
    $eventDescription = $_POST['event_description'];
    $eventStartDate = $_POST['event_start_date'];
    $eventEndDate = $_POST['event_end_date'];

    // Create database connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]);
        exit;
    }

    // Prepare the SQL statement to insert the event
    $sql = "INSERT INTO events (event_title, event_description, event_start_date, event_end_date) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to prepare SQL statement: ' . $conn->error]);
        exit;
    }

    // Bind the parameters
    $stmt->bind_param("ssss", $eventTitle, $eventDescription, $eventStartDate, $eventEndDate);

    // Execute the query
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Event saved successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save event: ' . $stmt->error]);
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request, missing parameters']);
}

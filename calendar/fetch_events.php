<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'intellidoc');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch events from the database
$sql = "SELECT id, title, start_date AS start, end_date AS end FROM events";
$result = $conn->query($sql);

$events = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}

// Send events as JSON
header('Content-Type: application/json');
echo json_encode($events);

$conn->close();

<?php
include_once 'config.php';
include_once 'functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Debugging: Log incoming request data
error_log("Incoming Request Data: " . json_encode($data));

if (!isset($data['facility_id'], $data['date'], $data['start_time'], $data['end_time'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$facility_id = (int)$data['facility_id'];
$room_id = isset($data['room_id']) ? (int)$data['room_id'] : null;
$date = $data['date'];
$start_time = $data['start_time'];
$end_time = $data['end_time'];

// Standardize formats before checking conflicts
$date = date('Y-m-d', strtotime($data['date']));       // Always YYYY-MM-DD
$start_time = date('H:i:s', strtotime($start_time));   // Always 24-hour format
$end_time = date('H:i:s', strtotime($end_time));       // Always 24-hour format

// Debugging: Log parsed variables
error_log("Checking Conflicts for Facility ID: $facility_id, Room ID: " . ($room_id ?? 'NULL') . ", Date: $date, Time: $start_time - $end_time");

// Run conflict check
$conflicts = checkBookingConflict($conn, $facility_id, $room_id, $date, $start_time, $end_time);

// Debugging: Log conflicts found
error_log("Conflicts Found: " . json_encode($conflicts));

$hasConflicts = !empty($conflicts);
$existingBookings = getExistingBookings($conn, $facility_id, $date);

$available_slots = [];
if ($hasConflicts) {
    $available_slots = findNextAvailableSlot($conn, $facility_id, $room_id, $date, $start_time, $end_time);
}

// Debugging: Log final API response
$response = [
    'hasConflicts' => $hasConflicts,
    'conflicts' => $conflicts,
    'existingBookings' => $existingBookings,
    'suggestedSlots' => $available_slots
];

error_log("Final Response: " . json_encode($response));
echo json_encode($response);

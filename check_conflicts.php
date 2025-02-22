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

// Validate required fields
if (!isset($data['facility_id'], $data['date'], $data['start_time'], $data['end_time'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Parse input data
$facility_id = (int)$data['facility_id'];
$room_id = isset($data['room_id']) ? (int)$data['room_id'] : null;
$date = date('Y-m-d', strtotime($data['date']));  // Ensure YYYY-MM-DD format
$start_time = date('H:i:s', strtotime($data['start_time']));  // Ensure 24-hour format
$end_time = date('H:i:s', strtotime($data['end_time']));  // Ensure 24-hour format

// Debugging: Log parsed variables
error_log("Checking Conflicts for Facility ID: $facility_id, Room ID: " . ($room_id ?? 'NULL') . ", Date: $date, Time: $start_time - $end_time");

// Prevent booking in restricted hours (12 AM - 2:59 AM)
if (!isValidBookingTime($start_time) || !isValidBookingTime($end_time)) {
    echo json_encode([
        'hasConflicts' => true,
        'conflicts' => [['message' => 'Bookings are not allowed between 12 AM and 2:59 AM']],
        'existingBookings' => [],
        'suggestedSlots' => []
    ]);
    exit;
}

// Run conflict check
$conflicts = checkBookingConflict($conn, $facility_id, $room_id, $date, $start_time, $end_time);

// Debugging: Log conflicts found
error_log("Conflicts Found: " . json_encode($conflicts));

$hasConflicts = !empty($conflicts);
$existingBookings = getExistingBookings($conn, $facility_id, $date);

// If conflicts exist, find available slots
$suggestedSlots = [];
if ($hasConflicts) {
    $suggestedSlots = findNextAvailableSlot($conn, $facility_id, $room_id, $date, $start_time, $end_time);

    // If no valid slots found, suggest next available date
    if (empty($suggestedSlots)) {
        for ($i = 1; $i <= 7; $i++) {  // Check up to 7 days ahead
            $next_date = date('Y-m-d', strtotime($date . " +$i days"));
            $available_slots = findNextAvailableSlot($conn, $facility_id, $room_id, $next_date, "08:00:00", "10:00:00");

            if (!empty($available_slots)) {
                $suggestedSlots = $available_slots;
                break;
            }
        }
    }
}

// Debugging: Log final API response
$response = [
    'hasConflicts' => $hasConflicts,
    'conflicts' => $conflicts,
    'existingBookings' => $existingBookings,
    'suggestedSlots' => $suggestedSlots
];

error_log("Final Response: " . json_encode($response));
echo json_encode($response);

error_log("Final Response: " . json_encode($response));

if (empty($suggestedSlots)) {
    error_log("No suggested slots were found!");
}

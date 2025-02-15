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

// Check for conflicts
$conflicts = checkBookingConflict($conn, $facility_id, $room_id, $date, $start_time, $end_time);

// Get existing bookings for the day
$existingBookings = getExistingBookings($conn, $facility_id, $date);

// Always treat any existing booking (Pending or Confirmed) as a conflict if times overlap
$hasConflicts = !empty($conflicts);

// Debug information
$debug = [
    'request' => [
        'facility_id' => $facility_id,
        'room_id' => $room_id,
        'date' => $date,
        'start_time' => $start_time,
        'end_time' => $end_time
    ],
    'found_conflicts' => $hasConflicts,
    'num_conflicts' => count($conflicts),
    'num_existing_bookings' => count($existingBookings)
];

error_log("Conflict check debug info: " . json_encode($debug));

// Only look for alternative slots if there are conflicts
$available_slots = [];
if ($hasConflicts) {
    $available_slots = findNextAvailableSlot(
        $conn,
        $facility_id,
        $room_id,
        $date,
        $start_time,
        $end_time
    );
}

echo json_encode([
    'hasConflicts' => $hasConflicts,
    'conflicts' => $conflicts,
    'existingBookings' => $existingBookings,
    'suggestedSlots' => $available_slots,
    'debug' => $debug
]);

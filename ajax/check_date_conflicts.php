<?php
session_start();
require_once '../database.php';

// Ensure clean output - no errors or warnings will be displayed
error_reporting(0);
ini_set('display_errors', 0);

// Set JSON header
header('Content-Type: application/json');

try {
    // Validate session
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access');
    }

    // Get and decode JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['startDate']) || !isset($input['endDate'])) {
        throw new Exception('Missing required dates');
    }

    $startDate = $input['startDate'];
    $endDate = $input['endDate'];
    $userId = $_SESSION['user_id'];

    // Query for existing proposals and bookings
    $sql = "SELECT 
        'proposal' as type,
        ap.activity_title as title,
        ap.activity_date as start_date,
        ap.end_activity_date as end_date,
        ap.club_name,
        ap.status,
        u.full_name as submitted_by,
        b.facility_id,
        f.name as facility_name,
        GROUP_CONCAT(r.room_number) as room_numbers,
        TIME_FORMAT(b.start_time, '%h:%i %p') as start_time,
        TIME_FORMAT(b.end_time, '%h:%i %p') as end_time
    FROM activity_proposals ap
    LEFT JOIN users u ON ap.user_id = u.id
    LEFT JOIN bookings b ON b.activity_proposal_id = ap.proposal_id
    LEFT JOIN facilities f ON b.facility_id = f.id
    LEFT JOIN booking_rooms br ON b.id = br.booking_id
    LEFT JOIN rooms r ON br.room_id = r.id
    WHERE (
        (ap.activity_date <= ? AND ap.end_activity_date >= ?) OR
        (ap.activity_date BETWEEN ? AND ?) OR
        (ap.end_activity_date BETWEEN ? AND ?)
    )
    AND ap.status IN ('confirmed', 'pending', 'received')
    GROUP BY ap.proposal_id";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssss",
        $endDate,
        $startDate,
        $startDate,
        $endDate,
        $startDate,
        $endDate
    );
    $stmt->execute();
    $result = $stmt->get_result();

    $conflicts = [];
    while ($row = $result->fetch_assoc()) {
        $conflicts[] = $row;
    }

    echo json_encode([
        'success' => true,
        'conflicts' => $conflicts
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();

<?php
include_once 'config.php';

header('Content-Type: application/json');

// Ensure request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['start_date'], $data['end_date'])) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$start_date = date('Y-m-d', strtotime($data['start_date']));
$end_date = date('Y-m-d', strtotime($data['end_date']));

// Set timezone to prevent inconsistencies
$conn->query("SET time_zone = '+08:00'");

// Query to check overlapping activity proposals
$sql_proposals = "
    SELECT ap.proposal_id, ap.activity_title, ap.club_name, ap.activity_date, ap.end_activity_date, ap.status, 
           b.facility_id, f.name AS facility_name
    FROM activity_proposals ap
    LEFT JOIN bookings b ON ap.proposal_id = b.activity_proposal_id
    LEFT JOIN facilities f ON b.facility_id = f.id
    WHERE (
        (? BETWEEN ap.activity_date AND ap.end_activity_date)
        OR
        (? BETWEEN ap.activity_date AND ap.end_activity_date)
        OR
        (ap.activity_date BETWEEN ? AND ?)
        OR
        (ap.end_activity_date BETWEEN ? AND ?)
    )
";

$stmt_proposals = $conn->prepare($sql_proposals);
$stmt_proposals->bind_param('ssssss', $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
$stmt_proposals->execute();
$result_proposals = $stmt_proposals->get_result();

$conflicts = [];

while ($row = $result_proposals->fetch_assoc()) {
    $conflict_entry = [
        'activity_title' => $row['activity_title'],
        'club_name' => $row['club_name'],
        'start_date' => date('F d, Y', strtotime($row['activity_date'])),
        'end_date' => date('F d, Y', strtotime($row['end_activity_date'])),
        'status' => $row['status'], // Include the status
        'facility_name' => $row['facility_name'] ?? 'No facility booked'
    ];
    $conflicts[] = $conflict_entry;
}

if (!empty($conflicts)) {
    echo json_encode(['conflict' => true, 'conflicts' => $conflicts]);
} else {
    echo json_encode(['conflict' => false]);
}

<?php
session_start();
require_once '../../database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

$facilityId = $_POST['facilityId'] ?? null;

if (!$facilityId) {
    die(json_encode(['success' => false, 'message' => 'Facility ID is required']));
}

$sql = "SELECT 
    b.id,
    b.booking_date,
    b.start_time,
    b.end_time,
    b.status,
    f.name as facility_name,
    c.club_name,
    c.acronym,
    ap.activity_title,
    GROUP_CONCAT(r.room_number) as room_numbers,
    u.full_name as booked_by
FROM bookings b
JOIN facilities f ON b.facility_id = f.id
LEFT JOIN users u ON b.user_id = u.id
LEFT JOIN club_memberships cm ON u.id = cm.user_id
LEFT JOIN clubs c ON cm.club_id = c.club_id
LEFT JOIN activity_proposals ap ON b.activity_proposal_id = ap.proposal_id
LEFT JOIN booking_rooms br ON b.id = br.booking_id
LEFT JOIN rooms r ON br.room_id = r.id
WHERE b.facility_id = ?
GROUP BY b.id
ORDER BY b.booking_date DESC, b.start_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $facilityId);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $row['booking_date'] = date('F d, Y', strtotime($row['booking_date']));
    $row['start_time'] = date('h:i A', strtotime($row['start_time']));
    $row['end_time'] = date('h:i A', strtotime($row['end_time']));
    $bookings[] = $row;
}

echo json_encode(['success' => true, 'bookings' => $bookings]);

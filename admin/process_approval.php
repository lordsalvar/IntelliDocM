<?php
include '../database.php';
$conn = getDbConnection();

$requestId = $_GET['id'];
$action = $_GET['action'];

if (!in_array($action, ['approve', 'reject'])) {
    die('Invalid action.');
}

$status = $action === 'approve' ? 'approved' : 'rejected';

// Update the block_requests table
$sql = "UPDATE block_requests SET status = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $status, $requestId);

if ($stmt->execute()) {
    if ($action === 'approve') {
        // Fetch the details of the approved request
        $fetchRequestSql = "SELECT club_id, facility_id, date FROM block_requests WHERE id = ?";
        $fetchStmt = $conn->prepare($fetchRequestSql);
        $fetchStmt->bind_param("i", $requestId);
        $fetchStmt->execute();
        $result = $fetchStmt->get_result();
        $request = $result->fetch_assoc();

        // Insert into facility_availability
        $availabilitySql = "INSERT INTO facility_availability (facility_id, date, status) VALUES (?, ?, 'blocked')";
        $availabilityStmt = $conn->prepare($availabilitySql);
        $availabilityStmt->bind_param("is", $request['facility_id'], $request['date']);
        $availabilityStmt->execute();
    }
    header("Location: admin_panel.php?msg=" . ucfirst($status) . " successfully!");
} else {
    echo "Error: " . $stmt->error;
}

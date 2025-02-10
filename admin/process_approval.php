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
    // Fetch the details of the approved/rejected request, including the facility name
    $fetchRequestSql = "
        SELECT br.requested_by, br.facility_id, br.date, f.name AS facility_name
        FROM block_requests br
        JOIN facilities f ON br.facility_id = f.id
        WHERE br.id = ?";
    $fetchStmt = $conn->prepare($fetchRequestSql);
    $fetchStmt->bind_param("i", $requestId);
    $fetchStmt->execute();
    $result = $fetchStmt->get_result();
    $request = $result->fetch_assoc();
    $fetchStmt->close();

    if ($request) {
        $user_id = $request['requested_by']; // Get the user who made the request
        $facility_name = $request['facility_name']; // Get the facility name
        $date = $request['date'];

        // ✅ Insert Notification for the User with Facility Name
        $message = ($action === 'approve')
            ? "Your block request for '$facility_name' on $date has been approved."
            : "Your block request for '$facility_name' on $date has been rejected.";

        $insertNotificationSql = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
        $insertNotificationStmt = $conn->prepare($insertNotificationSql);
        $insertNotificationStmt->bind_param("is", $user_id, $message);
        $insertNotificationStmt->execute();
        $insertNotificationStmt->close();
    }

    // ✅ Insert into facility_availability only if approved
    if ($action === 'approve') {
        $availabilitySql = "INSERT INTO facility_availability (facility_id, date, status) VALUES (?, ?, 'blocked')";
        $availabilityStmt = $conn->prepare($availabilitySql);
        $availabilityStmt->bind_param("is", $request['facility_id'], $date);
        $availabilityStmt->execute();
        $availabilityStmt->close();
    }

    // Redirect back to admin panel with a success message
    header("Location: http://localhost/main/intellidocm/admin/view_proposals.php?msg=" . ucfirst($status) . " successfully!");
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();

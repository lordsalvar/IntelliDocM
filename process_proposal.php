<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'system_log/activity_log.php';
include_once 'phpqrcode/qrlib.php';

header('Content-Type: application/json');

try {
    // Basic validation
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method");
    }

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        throw new Exception("Invalid CSRF token");
    }

    // Add flag to prevent duplicate submissions
    if (isset($_SESSION['last_submission_time'])) {
        $time_difference = time() - $_SESSION['last_submission_time'];
        if ($time_difference < 5) { // 5 seconds threshold
            die(json_encode(['error' => 'Please wait before submitting again.']));
        }
    }
    $_SESSION['last_submission_time'] = time();

    // Add submission tracking
    $submission_id = uniqid('sub_', true);
    $_SESSION['current_submission'] = $submission_id;

    // Basic data collection
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        throw new Exception("User not authenticated");
    }

    // Collect form data
    $activity_title = sanitize_input($_POST['activityTitle']);
    $activity_type = isset($_POST['activityType']) ? implode(", ", $_POST['activityType']) : "";
    $objectives = sanitize_input($_POST['objectives']);
    $venue = sanitize_input($_POST['venue']);
    $target_participants = sanitize_input($_POST['targetParticipants']);
    $expected_participants = (int)sanitize_input($_POST['expectedParticipants']);

    // Additional form data collection
    $club_data = getClubData($conn, $user_id);
    $applicant_details = getApplicantDetails($conn, $user_id);
    $applicant_name = $applicant_details['applicant_name'];

    // Additional fields
    $club_name = isset($club_data['club_name']) ? $club_data['club_name'] : sanitize_input($_POST['organizationName']);
    $acronym = isset($club_data['acronym']) ? $club_data['acronym'] : sanitize_input($_POST['acronym']);
    $club_type = isset($club_data['club_type']) ? $club_data['club_type'] : sanitize_input($_POST['clubType']);
    $designation = isset($club_data['designation']) ? $club_data['designation'] : sanitize_input($_POST['designation']);
    $address = sanitize_input($_POST['address']);
    $activity_date = sanitize_input($_POST['start_date'] ?? '');
    $end_activity_date = sanitize_input($_POST['end_date']);
    $start_time = sanitize_input($_POST['startTime'] ?? '');
    $end_time = sanitize_input($_POST['endTime'] ?? '');

    // Program categories
    $program_category = implode(", ", array_filter(array_map('sanitize_input', [
        $_POST['omp'] ?? null,
        $_POST['ksd'] ?? null,
        $_POST['ct'] ?? null,
        $_POST['srf'] ?? null,
        $_POST['rpInitiative'] ?? null,
        $_POST['cesa'] ?? null,
        $_POST['other_program'] ?? null
    ])));

    // Signature handling
    $applicant_signature = sanitize_input($_POST['applicantSignature'] ?? '');
    $applicant_designation = sanitize_input($_POST['applicantDesignation']);
    $applicant_date_filed = date('Y-m-d');

    // Get additional details
    $moderator_data = isset($club_data['club_id']) ? getModeratorData($conn, $club_data['club_id']) : ['moderator_name' => ''];
    $moderator_name = $moderator_data['moderator_name'];
    $dean_data = isset($club_data['club_id']) ? getDeanData($conn, $club_data['club_id']) : ['dean_name' => ''];
    $dean_name = $dean_data['dean_name'];

    // Update the insert statement to include all fields
    $stmt = $conn->prepare("
        INSERT INTO activity_proposals (
            user_id, club_name, acronym, club_type, designation, activity_title, 
            activity_type, objectives, program_category, venue, address, 
            activity_date, end_activity_date, start_time, end_time, target_participants, 
            expected_participants, applicant_name, applicant_signature, 
            applicant_designation, applicant_date_filed, applicant_contact, 
            moderator_name, status
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Received'
        )
    ");

    $stmt->bind_param(
        "issssssssssssssisssssss",
        $user_id,
        $club_name,
        $acronym,
        $club_type,
        $designation,
        $activity_title,
        $activity_type,
        $objectives,
        $program_category,
        $venue,
        $address,
        $activity_date,
        $end_activity_date,
        $start_time,
        $end_time,
        $target_participants,
        $expected_participants,
        $applicant_name,
        $applicant_signature,
        $applicant_designation,
        $applicant_date_filed,
        $applicant_details['applicant_contact'],
        $moderator_name
    );

    if (!$stmt->execute()) {
        throw new Exception("Database error: " . $stmt->error);
    }

    $proposal_id = $stmt->insert_id;

    // Update the facility bookings processing section
    if (isset($_POST['facilityBookings']) && is_array($_POST['facilityBookings'])) {
        foreach ($_POST['facilityBookings'] as $booking) {
            if (empty($booking['facility'])) continue;

            // Process each time slot for this facility
            if (isset($booking['slots']) && is_array($booking['slots'])) {
                foreach ($booking['slots'] as $slot) {
                    // Skip empty slots
                    if (empty($slot['date']) || empty($slot['start']) || empty($slot['end'])) {
                        continue;
                    }

                    // Insert booking for each time slot
                    $stmt = $conn->prepare("
                        INSERT INTO bookings (
                            facility_id, 
                            user_id, 
                            activity_proposal_id, 
                            booking_date, 
                            start_time, 
                            end_time, 
                            status
                        ) VALUES (?, ?, ?, ?, ?, ?, 'Pending')
                    ");

                    $stmt->bind_param(
                        "iiisss",
                        $booking['facility'],
                        $user_id,
                        $proposal_id,
                        $slot['date'],
                        $slot['start'],
                        $slot['end']
                    );

                    if ($stmt->execute()) {
                        $booking_id = $stmt->insert_id;

                        // Process room booking if selected
                        if (!empty($booking['room'])) {
                            $roomStmt = $conn->prepare("INSERT INTO booking_rooms (booking_id, room_id) VALUES (?, ?)");
                            $roomStmt->bind_param("ii", $booking_id, $booking['room']);
                            $roomStmt->execute();
                            $roomStmt->close();
                        }
                    }
                    $stmt->close();
                }
            }
        }
    }

    // Generate QR code after successful insertion
    if ($proposal_id) {
        $qrData = "proposal_id=" . urlencode($proposal_id) . "&signed_by=" . urlencode($applicant_name);
        $qrDirectory = "client_qr_codes";
        if (!is_dir($qrDirectory)) {
            mkdir($qrDirectory, 0777, true);
        }
        $qrFilePath = $qrDirectory . "/applicant_qr_" . $proposal_id . ".png";
        QRcode::png($qrData, $qrFilePath, QR_ECLEVEL_L, 5);

        // Update proposal with QR code path
        $updateStmt = $conn->prepare("UPDATE activity_proposals SET applicant_signature = ? WHERE proposal_id = ?");
        $updateStmt->bind_param("si", $qrFilePath, $proposal_id);
        $updateStmt->execute();
        $updateStmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'Proposal submitted successfully!',
            'proposal_id' => $proposal_id,
            'redirect' => '/main/IntelliDocM/client.php'
        ]);
    }
} catch (Exception $e) {
    error_log("Proposal submission error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'redirect' => '/main/IntelliDocM/client.php'  // Add redirect even on error
    ]);
}

// If we somehow get here without redirecting, redirect anyway
if (!headers_sent()) {
    header('Location: /main/IntelliDocM/client.php');
    exit();
}

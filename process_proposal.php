<?php
// process_proposal.php

include_once 'config.php';
include_once 'functions.php';
include_once 'system_log/activity_log.php';
include_once 'phpqrcode/qrlib.php';

// Ensure the request is a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Check CSRF token validity
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }

    // Retrieve user and club information
    $user_id = $_SESSION['user_id'];
    $club_data = getClubData($conn, $user_id);
    $applicant_details = getApplicantDetails($conn, $user_id);
    $applicant_name = $applicant_details['applicant_name'];

    // Collect and sanitize form data
    $club_name = isset($club_data['club_name']) ? $club_data['club_name'] : sanitize_input($_POST['organizationName']);
    $acronym = isset($club_data['acronym']) ? $club_data['acronym'] : sanitize_input($_POST['acronym']);
    $club_type = isset($club_data['club_type']) ? $club_data['club_type'] : sanitize_input($_POST['clubType']);
    $designation = isset($club_data['designation']) ? $club_data['designation'] : sanitize_input($_POST['designation']);
    $activity_title = sanitize_input($_POST['activityTitle']);
    $activity_type = isset($_POST['activityType']) ? implode(", ", array_map('sanitize_input', $_POST['activityType'])) : "";
    $objectives = sanitize_input($_POST['objectives']);
    $program_category = implode(", ", array_filter(array_map('sanitize_input', [
        $_POST['omp'] ?? null,
        $_POST['ksd'] ?? null,
        $_POST['ct'] ?? null,
        $_POST['srf'] ?? null,
        $_POST['rpInitiative'] ?? null,
        $_POST['cesa'] ?? null,
        $_POST['other_program'] ?? null
    ])));
    $venue = sanitize_input($_POST['venue']);
    $address = sanitize_input($_POST['address']);
    $activity_date = sanitize_input($_POST['start_date'] ?? '');
    $end_activity_date = sanitize_input($_POST['end_date']);
    $start_time = sanitize_input($_POST['startTime'] ?? '');
    $end_time = sanitize_input($_POST['endTime'] ?? '');
    $target_participants = sanitize_input($_POST['targetParticipants']);
    $expected_participants = (int)sanitize_input($_POST['expectedParticipants']);

    // Handle signatures and file uploads
    $applicant_signature = sanitize_input($_POST['applicantSignature'] ?? '');
    $applicant_designation = sanitize_input($_POST['applicantDesignation']);
    $applicant_date_filed = date('Y-m-d');

    $moderator_signature = null;
    if (isset($_FILES['moderatorSignature']) && is_uploaded_file($_FILES['moderatorSignature']['tmp_name'])) {
        $moderator_signature = file_get_contents($_FILES['moderatorSignature']['tmp_name']);
    }

    $faculty_signature = sanitize_input($_POST['facultySignature'] ?? '');
    $faculty_contact   = sanitize_input($_POST['facultyContact'] ?? '');

    $dean_signature = null;
    if (isset($_FILES['deanSignature']) && is_uploaded_file($_FILES['deanSignature']['tmp_name'])) {
        $dean_signature = file_get_contents($_FILES['deanSignature']['tmp_name']);
    }

    // Default status and rejection reason
    $status = "Received";
    $rejection_reason = null;

    // Get additional details from club data (if available)
    $moderator_data = isset($club_data['club_id']) ? getModeratorData($conn, $club_data['club_id']) : ['moderator_name' => ''];
    $moderator_name = $moderator_data['moderator_name'];
    $dean_data = isset($club_data['club_id']) ? getDeanData($conn, $club_data['club_id']) : ['dean_name' => ''];
    $dean_name = $dean_data['dean_name'];

    // Prepare the SQL statement for inserting the proposal
    $stmt = $conn->prepare("
    INSERT INTO activity_proposals (
        user_id, club_name, acronym, club_type, designation, activity_title, 
        activity_type, objectives, program_category, venue, address, 
        activity_date, end_activity_date, start_time, end_time, target_participants, 
        expected_participants, applicant_name, applicant_signature, 
        applicant_designation, applicant_date_filed, applicant_contact, 
        moderator_name, moderator_signature, moderator_date_signed, 
        moderator_contact, faculty_signature, faculty_contact, 
        dean_name, dean_signature, status, rejection_reason
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )
");


    $moderator_date_signed = null;
    $moderator_contact = null;
    $rejection_reason = null;


    $stmt->bind_param(
        "issssssssssssssissssssbsssssbsss",
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
        $moderator_name,
        $moderator_signature,
        $moderator_date_signed, // NULL passed as variable
        $moderator_contact, // NULL passed as variable
        $faculty_signature, // NULL passed as variable
        $faculty_contact, // NULL passed as variable
        $dean_name,
        $dean_signature, // NULL passed as variable
        $status,
        $rejection_reason // NULL passed as variable
    );



    if ($applicant_signature !== null) {
        $stmt->send_long_data(19, $applicant_signature);
    }
    if ($moderator_signature !== null) {
        $stmt->send_long_data(24, $moderator_signature);
    }
    if ($dean_signature !== null) {
        $stmt->send_long_data(29, $dean_signature);
    }


    // Execute the insert
    if ($stmt->execute()) {
        $proposal_id = $stmt->insert_id;

        // Generate a QR code for the proposal (adjust URL as needed)
        $qrData = "http://yourdomain.com/verify_qr.php?proposal_id=" . urlencode($proposal_id) . "&signed_by=" . urlencode($applicant_name);
        $qrDirectory = "client_qr_codes";
        if (!is_dir($qrDirectory)) {
            if (!mkdir($qrDirectory, 0777, true) && !is_dir($qrDirectory)) {
                die("Failed to create QR code directory: $qrDirectory");
            }
        }
        $qrFilePath = $qrDirectory . "/applicant_qr_" . $proposal_id . ".png";
        QRcode::png($qrData, $qrFilePath, QR_ECLEVEL_L, 5);

        // Update the record with the QR code path
        $updateSql = "UPDATE activity_proposals SET applicant_signature = ? WHERE proposal_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("si", $qrFilePath, $proposal_id);
        if ($updateStmt->execute()) {
            echo "<script>alert('Proposal submitted and QR code generated successfully!');</script>";
            echo "<script>window.location.href = '/main/IntelliDocM/client.php';</script>";
        } else {
            echo "<div class='alert alert-danger'>Error generating QR code: " . $updateStmt->error . "</div>";
        }
        $updateStmt->close();
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

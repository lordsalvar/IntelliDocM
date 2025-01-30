<?php
require_once 'phpqrcode/qrlib.php';
include 'database.php';
include 'system_log/activity_log.php';
session_start([
    'cookie_lifetime' => 3600, // Session expires after 1 hour
    'cookie_httponly' => true, // Prevent JavaScript access
    'cookie_secure' => isset($_SERVER['HTTPS']), // HTTPS only
    'use_strict_mode' => true // Strict session handling
]);

// Prevent session fixation
session_regenerate_id(true);

$username = $_SESSION['username'];
$userActivity = 'User visited Booking Form Page';

logActivity($username, $userActivity);

// Check user role
if ($_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$conn = getDbConnection();

// Function to fetch the user's club data
function getClubData($conn, $user_id)
{
    $sql = "SELECT c.club_name, c.acronym, c.club_type, cm.designation, cm.club_id
            FROM clubs c    
            JOIN club_memberships cm ON c.club_id = cm.club_id
            WHERE cm.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to fetch the user's contact number
function getUserContact($conn, $user_id)
{
    $sql = "SELECT contact FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['contact'] ?? '';
}

function getUserDesignation($conn, $user_id)
{
    $sql = "SELECT cm.designation 
            FROM club_memberships cm 
            WHERE cm.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['designation'] ?? '';
}

$userDesignation = getUserDesignation($conn, $userId);

// Fetch the contact number
$userContact = getUserContact($conn, $userId);

function getUserFullName($conn, $user_id)
{
    $sql = "SELECT full_name FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['full_name'] ?? '';
}

$userFullName = getUserFullName($conn, $userId);

// Ensure user ID is set in the session
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'title' => 'Error', 'message' => 'You must be logged in to make a block request.']);
    exit();
}

// Get the proposal ID from the URL
$proposalId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch the club data dynamically
$clubData = getClubData($conn, $userId);

if (!$clubData) {
    echo json_encode(['success' => false, 'title' => 'Error', 'message' => 'No club membership found for the logged-in user.']);
    exit();
}

$clubId = $clubData['club_id']; // Get the club ID from the fetched data

// Fetch approved facilities
function getApprovedFacilities($conn, $club_id)
{
    $sql = "SELECT f.id, f.name 
            FROM block_requests br
            JOIN facilities f ON br.facility_id = f.id
            WHERE br.club_id = ? 
            AND br.status = 'Approved'"; // Only fetch facilities with approved requests

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $club_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $facilities = [];
    while ($row = $result->fetch_assoc()) {
        $facilities[] = $row;
    }

    return $facilities;
}

$approvedFacilities = getApprovedFacilities($conn, $clubId);

// Function to get the facility ID based on the facility name
function getFacilityIdByName($facilityName)
{
    global $conn;
    $sql = "SELECT id FROM facilities WHERE name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $facilityName);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($facilityId);
    $stmt->fetch();
    return $facilityId;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }

    // Collect form data
    $userId = $_SESSION['user_id'];
    $clubName = $_POST['organization_nature'];
    $contactNumber = $_POST['contact_number'];
    $purpose = $_POST['purpose_request'];
    $dateOfUse = $_POST['date_of_use'];
    $proposalId = $_POST['proposal_id'];  // From the form

    // Insert booking details into the database
    $stmt = $conn->prepare("
        INSERT INTO bookings (user_id, club_name, contact_number, purpose, date_of_use, proposal_id)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issssi", $userId, $clubName, $contactNumber, $purpose, $dateOfUse, $proposalId);

    if ($stmt->execute()) {
        $bookingId = $stmt->insert_id;

        // Generate QR code data for requested_by_signature (Only for the requesting party)
        $qrData = "http://192.168.0.106/main/IntelliDocM/verify_qr/verify_booking.php?booking_id=" . urlencode($bookingId);

        // Define directory to save QR codes
        $qrDirectory = "client_qr_codes";
        if (!is_dir($qrDirectory)) {
            mkdir($qrDirectory, 0777, true);
        }

        // Define file path for the requested_by_signature QR code
        $requestedByQrFilePath = $qrDirectory . "/requested_by_qr_" . $bookingId . ".png";

        // Generate and save QR code for requested_by_signature (requested party)
        QRcode::png($qrData, $requestedByQrFilePath, QR_ECLEVEL_L, 5);

        // Update the database with the requested_by_signature QR code path
        $updateStmt = $conn->prepare("UPDATE bookings SET requested_by_signature = ? WHERE id = ?");
        $updateStmt->bind_param("si", $requestedByQrFilePath, $bookingId);
        $updateStmt->execute();

        // Optionally: Set QR codes for other signatures (will be done later, after approval)
        $updateStmt = $conn->prepare("UPDATE bookings SET ssc_signature = NULL, moderator_signature = NULL, security_signature = NULL, property_custodian_signature = NULL WHERE id = ?");
        $updateStmt->bind_param("i", $bookingId);
        $updateStmt->execute();

        // Handle the facilities data (if any facilities are selected)
        if (!empty($_POST['facilities'])) {
            // Prepare statement for inserting facilities into the booked_facilities table
            $facilityStmt = $conn->prepare("
                INSERT INTO booked_facilities (booking_id, facility_id, building_or_room, date_of_use, time_of_use, end_time_of_use)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            // Loop through each selected facility
            foreach ($_POST['facilities'] as $facilityName) {
                // Get the corresponding facility details
                $facilityId = getFacilityIdByName($facilityName);  // Get the facility ID based on name
                $buildingOrRoom = $_POST['building_or_room'][$facilityName] ?? '';
                $timeOfUse = $_POST['time_of_use'][$facilityName] ?? '';
                $endTimeOfUse = $_POST['end_time_of_use'][$facilityName] ?? '';

                // Insert facility data for each selected facility
                $facilityStmt->bind_param("iissss", $bookingId, $facilityId, $buildingOrRoom, $dateOfUse, $timeOfUse, $endTimeOfUse);
                $facilityStmt->execute();
            }

            // Close the facility statement after execution
            $facilityStmt->close();
        }

        echo "<script>alert('Booking submitted successfully! QR code for the requesting party generated.')</script>";
        echo "<script>window.location.href = '/main/IntelliDocM/client.php';</script>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

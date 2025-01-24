<?php
include '../database.php';
session_start([
    'cookie_lifetime' => 3600, // Session expires after 1 hour
    'cookie_httponly' => true, // Prevent JavaScript access
    'cookie_secure' => isset($_SERVER['HTTPS']), // HTTPS only
    'use_strict_mode' => true // Strict session handling
]);

// Prevent session fixation
session_regenerate_id(true);

// Check user role
if ($_SESSION['role'] !== 'client') {
    header('Location: ../login.php');
    exit();
}

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

// Ensure user ID is set in the session
if (!isset($_SESSION['user_id'])) {
    die('Error: You must be logged in to make a block request.');
}

$userId = $_SESSION['user_id'];

// Fetch the club data dynamically
$clubData = getClubData($conn, $userId);

if (!$clubData) {
    die('Error: No club membership found for the logged-in user.');
}

$clubId = $clubData['club_id']; // Get the club ID from the fetched data

// Get POST data
$facilityId = $_POST['facility'];
$date = $_POST['date'];

// Check if a block request already exists for the same facility and date
$checkSql = "SELECT id FROM block_requests WHERE facility_id = ? AND date = ? AND club_id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("isi", $facilityId, $date, $clubId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    die('Error: A block request for this facility and date already exists.');
}

// Insert block request
$sql = "INSERT INTO block_requests (club_id, facility_id, date, status, requested_by) 
        VALUES (?, ?, ?, 'pending', ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iisi", $clubId, $facilityId, $date, $userId);

if ($stmt->execute()) {
    echo "Block request submitted successfully!";
} else {
    echo "Error: " . $stmt->error;
}

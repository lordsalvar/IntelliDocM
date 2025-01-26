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
    echo json_encode(['success' => false, 'title' => 'Error', 'message' => 'You must be logged in to make a block request.']);
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch the club data dynamically
$clubData = getClubData($conn, $userId);

if (!$clubData) {
    echo json_encode(['success' => false, 'title' => 'Error', 'message' => 'No club membership found for the logged-in user.']);
    exit();
}

$clubId = $clubData['club_id']; // Get the club ID from the fetched data

// Get POST data
$facilities = $_POST['facilities'] ?? [];
$dates = $_POST['dates'] ?? [];

// Validate that the number of facilities matches the number of dates
if (count($facilities) !== count($dates)) {
    echo json_encode(['success' => false, 'title' => 'Error', 'message' => 'Mismatch between selected facilities and dates.']);
    exit();
}

// Initialize response arrays
$successCount = 0;
$errors = [];

// Process each facility-date pair
foreach ($facilities as $index => $facilityId) {
    $date = $dates[$index];

    // Check if a block request already exists for the same facility and date
    $checkSql = "SELECT id FROM block_requests WHERE facility_id = ? AND date = ? AND club_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("isi", $facilityId, $date, $clubId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $errors[] = "A block request for facility ID $facilityId on $date already exists.";
        continue;
    }

    // Insert block request
    $insertSql = "INSERT INTO block_requests (club_id, facility_id, date, status, requested_by) 
                  VALUES (?, ?, ?, 'pending', ?)";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->bind_param("iisi", $clubId, $facilityId, $date, $userId);

    if ($insertStmt->execute()) {
        $successCount++;
    } else {
        $errors[] = "Failed to insert block request for facility ID $facilityId on $date: " . $insertStmt->error;
    }
}

// Build response
if ($successCount > 0) {
    $message = "$successCount block request(s) submitted successfully.";
    if (!empty($errors)) {
        $message .= " However, the following errors occurred: " . implode(', ', $errors);
    }
    echo json_encode(['success' => true, 'title' => 'Partial Success', 'message' => $message]);
} else {
    echo json_encode(['success' => false, 'title' => 'Error', 'message' => implode(', ', $errors)]);
}
exit();

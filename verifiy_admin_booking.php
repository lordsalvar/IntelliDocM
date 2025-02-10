<?php
require_once 'phpqrcode/qrlib.php';
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit();
}

$proposalId = isset($_POST['proposal_id']) ? (int)$_POST['proposal_id'] : 0;
if ($proposalId <= 0) {
    http_response_code(400);
    echo "Invalid proposal_id.";
    exit();
}

// Connect to DB and fetch the booking
$conn = getDbConnection();
$sql = "SELECT * FROM bookings WHERE proposal_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $proposalId);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    $conn->close();
    http_response_code(404);
    echo "Booking not found for proposal_id = " . htmlspecialchars($proposalId);
    exit();
}

$bookingId = $booking['id'];

// Generate QR codes for verification
$qrDirectory = "qr_codes";
if (!is_dir($qrDirectory)) {
    mkdir($qrDirectory, 0777, true);
}

$roles = ['ssc', 'moderator', 'security', 'property_custodian'];
$qrPaths = [];

foreach ($roles as $role) {
    $qrPaths[$role] = generateSignatureQR($bookingId, $role, $qrDirectory);
}

// Update bookings table with generated QR paths
$updateSql = "UPDATE bookings SET ";
$params = [];
$types = "";

foreach ($roles as $role) {
    $column = $role . "_signature";
    $updateSql .= "$column = ?, ";
    $params[] = $qrPaths[$role];
    $types .= "s";
}

$updateSql = rtrim($updateSql, ", ") . " WHERE id = ?";
$params[] = $bookingId;
$types .= "i";

$updateStmt = $conn->prepare($updateSql);
$updateStmt->bind_param($types, ...$params);
$success = $updateStmt->execute();
$updateStmt->close();
$conn->close();

if ($success) {
    echo "Signatures generated successfully.";
} else {
    http_response_code(500);
    echo "Failed to update signatures.";
}

// Helper function to generate QR codes
function generateSignatureQR($bookingId, $role, $directory)
{
    $qrData = "http://10.6.8.72/main/IntelliDocM/verify_admin_booking_qr.php?proposal_id="
        . urlencode($bookingId) . "&role=" . urlencode($role);
    $filePath = "$directory/{$role}_qr_$bookingId.png";
    QRcode::png($qrData, $filePath, QR_ECLEVEL_L, 5);
    return $filePath;
}

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

// 1) Connect to DB and fetch the booking
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

// 2) Generate all 4 QR codes
$qrDirectory = "qr_codes"; // or any folder you prefer
if (!is_dir($qrDirectory)) {
    mkdir($qrDirectory, 0777, true);
}

// We'll create separate QR codes with role-based parameters
$sscQrFilePath           = generateSignatureQR($bookingId, 'ssc',           $qrDirectory);
$moderatorQrFilePath     = generateSignatureQR($bookingId, 'moderator',     $qrDirectory);
$securityQrFilePath      = generateSignatureQR($bookingId, 'security',      $qrDirectory);
$propertyCustodianQrPath = generateSignatureQR($bookingId, 'property',      $qrDirectory);

// 3) Update bookings table
$updateSql = "
    UPDATE bookings
    SET ssc_signature = ?,
        moderator_signature = ?,
        security_signature = ?,
        property_custodian_signature = ?
    WHERE id = ?
";
$updateStmt = $conn->prepare($updateSql);
$updateStmt->bind_param(
    "ssssi",
    $sscQrFilePath,
    $moderatorQrFilePath,
    $securityQrFilePath,
    $propertyCustodianQrPath,
    $bookingId
);
$success = $updateStmt->execute();
$updateStmt->close();
$conn->close();

if ($success) {
    echo "Signatures generated successfully.";
} else {
    http_response_code(500);
    echo "Failed to update signatures.";
}

// -------------- Helper function ----------------
function generateSignatureQR($bookingId, $role, $directory)
{
    // Build the data you'd want to embed in the QR
    // e.g. pass booking_id and role to the verify page
    $qrData = "http://localhost/main/IntelliDocM/verify_qr/verify_booking.php?booking_id="
        . urlencode($bookingId) . "&role=" . urlencode($role);

    // Generate file path
    $filePath = $directory . "/" . $role . "_qr_" . $bookingId . ".png";

    // Use the QRcode library to create the image
    QRcode::png($qrData, $filePath, QR_ECLEVEL_L, 5);

    return $filePath;
}

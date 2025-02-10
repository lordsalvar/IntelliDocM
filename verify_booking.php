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

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Please log in.'
    ]);
    exit();
}

$conn = getDbConnection();

// Get booking ID from GET request
if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    exit('Invalid booking ID.');
}
$bookingId = (int) $_GET['booking_id'];

// Fetch booking details
$sql = "SELECT b.*, u.full_name, u.contact FROM bookings b
        JOIN users u ON b.user_id = u.id
        WHERE b.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    exit('Booking record not found.');
}

// Fetch booked facilities
$sqlFacilities = "SELECT f.name, bf.building_or_room, bf.date_of_use, bf.time_of_use, bf.end_time_of_use
                  FROM booked_facilities bf
                  JOIN facilities f ON bf.facility_id = f.id
                  WHERE bf.booking_id = ?";
$stmt = $conn->prepare($sqlFacilities);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$facilitiesResult = $stmt->get_result();
$facilities = [];
while ($row = $facilitiesResult->fetch_assoc()) {
    $facilities[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Booking QR - Cor Jesu College</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h2 class="text-center">Booking Verification</h2>
        <div class="card p-4 mt-3">
            <h4>Booking Details</h4>
            <p><strong>Requested By:</strong> <?= htmlspecialchars($booking['full_name']) ?></p>
            <p><strong>Contact Number:</strong> <?= htmlspecialchars($booking['contact']) ?></p>
            <p><strong>Club Name:</strong> <?= htmlspecialchars($booking['club_name']) ?></p>
            <p><strong>Purpose:</strong> <?= htmlspecialchars($booking['purpose']) ?></p>
        </div>

        <div class="card p-4 mt-3">
            <h4>Booked Facilities</h4>
            <?php if (!empty($facilities)): ?>
                <ul>
                    <?php foreach ($facilities as $facility): ?>
                        <li>
                            <strong>Facility:</strong> <?= htmlspecialchars($facility['name']) ?><br>
                            <strong>Building/Room:</strong> <?= htmlspecialchars($facility['building_or_room']) ?><br>
                            <strong>Date of Use:</strong> <?= htmlspecialchars($facility['date_of_use']) ?><br>
                            <strong>Time:</strong> <?= htmlspecialchars($facility['time_of_use']) ?> - <?= htmlspecialchars($facility['end_time_of_use']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No facilities found for this booking.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>

</html>
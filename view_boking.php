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

$username = $_SESSION['username'] ?? '';
$userActivity = 'User visited Booking Form Page';

// Log user activity
logActivity($username, $userActivity);

// Redirect non-clients to the login page
if ($_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    echo json_encode(['success' => false, 'title' => 'Error', 'message' => 'You must be logged in to access this page.']);
    exit();
}

$conn = getDbConnection();

// Generate a CSRF token for the session if not already set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function getUserDetails($conn, $userId) {
    $userDetails = [];

    // Join the clubs table to access the club_name, acronym, and club_type
    $stmt = $conn->prepare("SELECT u.contact, u.full_name, c.club_name, c.acronym, c.club_type, cm.designation
                            FROM users u
                            JOIN club_memberships cm ON u.id = cm.user_id
                            JOIN clubs c ON cm.club_id = c.club_id
                            WHERE u.id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $userDetails = $row;
    }
    $stmt->close();

    return $userDetails;
}

$userDetails = getUserDetails($conn, $userId);

if (empty($userDetails)) {
    echo json_encode(['success' => false, 'title' => 'Error', 'message' => 'No user details found.']);
    exit();
}

$proposalId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

function getApprovedFacilities($conn, $userId) {
    $facilities = [];
    $stmt = $conn->prepare("SELECT f.id, f.name
                            FROM facilities f
                            JOIN facility_availability fa ON f.id = fa.facility_id
                            WHERE fa.status = 'Approved'");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $facilities[] = $row;
    }
    $stmt->close();

    return $facilities;
}

$approvedFacilities = getApprovedFacilities($conn, $userId);

// Initialize variables to manage modal display
$hasPendingRequests = false; // Assuming function to check for pending requests
$hasNoRequests = empty($approvedFacilities); // True if no facilities found

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token check
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        die('Invalid CSRF token');
    }

    // Sanitize and validate input
    $organizationNature = $userDetails['club_name'];
    $contactNumber = filter_input(INPUT_POST, 'contact_number', FILTER_SANITIZE_STRING);
    $purpose = filter_input(INPUT_POST, 'purpose_request', FILTER_SANITIZE_STRING);
    $dateOfUse = filter_input(INPUT_POST, 'date_of_use', FILTER_SANITIZE_STRING);

    // Insert sanitized data into the database
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, club_name, contact_number, purpose, date_of_use)
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $userId, $organizationNature, $contactNumber, $purpose, $dateOfUse);
    if ($stmt->execute()) {
        $bookingId = $stmt->insert_id;

        // Generate QR code data
        $qrData = "http://192.168.0.106/main/IntelliDocM/verify_qr/verify_booking.php?booking_id=" . urlencode($bookingId);

        // Define directory to save QR codes
        $qrDirectory = "client_qr_codes";
        if (!is_dir($qrDirectory)) {
            mkdir($qrDirectory, 0777, true);
        }

        // Define file path for the QR code
        $qrFilePath = $qrDirectory . "/booking_qr_" . $bookingId . ".png";

        // Generate and save QR code
        QRcode::png($qrData, $qrFilePath, QR_ECLEVEL_L, 5);

        // Update the database with the QR code path
        $updateStmt = $conn->prepare("UPDATE bookings SET qr_code_path = ? WHERE id = ?");
        $updateStmt->bind_param("si", $qrFilePath, $bookingId);
        $updateStmt->execute();
        $updateStmt->close();

        echo "<script>alert('Booking submitted successfully! QR code generated.')</script>";
        echo "<script>window.location.href = '/main/IntelliDocM/client.php';</script>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request for Use of School Facilities - Cor Jesu College</title>
    <!-- Updated to Bootstrap 5 for better design options -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS for additional styling -->
    <link rel="stylesheet" href="css/boking.css">

</head>

<body>
    <div class="container mt-5">
        <!-- Overlay Box -->
        <div class="overlay-box">
            <p><strong>Index No.:</strong> <u> 7.3 </u></p>
            <p><strong>Revision No.:</strong> <u> 00 </u></p>
            <p><strong>Effective Date:</strong> <u> 05/16/24 </u></p>
            <p><strong>Control No.:</strong> ___________</p>
        </div>
        <div class="header-content">
            <img src="css/img/cjc_logo.png" alt="Logo" class="header-logo">
            <div class="header-text">
                <h2 class="text-center text-uppercase">Cor Jesu College, Inc.</h2>
                <div class="line yellow-line"></div>
                <div class="line blue-line"></div>
                <p class="text-center">Sacred Heart Avenue, Digos City, Province of Davao del Sur, Philippines</p>
                <p class="text-center">Tel. No.: (082) 553-2433 local 101 • Fax No.: (082) 553-2333 • www.cjc.edu.ph</p>
            </div>
        </div>

        <div class="text-center mb-4">
            <h4 class="text-uppercase">Request for the Use of School Facilities</h4>
        </div>

        <form>
            <!-- Requesting Party Information -->
            <div class="form-section mb-4">
                <h3>Requesting Party</h3>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nature of Department/Group/Organization</label>
                        <input type="text" class="form-control" name="organization_nature" value="<?= htmlspecialchars($clubData['club_name']) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact Number</label>
                        <input type="text" class="form-control" name="contact_number" value="<?= htmlspecialchars($userContact) ?>" readonly>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Purpose of Request</label>
                        <input type="text" class="form-control" name="purpose_request" value="<?= htmlspecialchars($activityTitle) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date of Use</label>
                        <input type="date" class="form-control" name="date_of_use">
                    </div>
                </div>
            </div>



            <div id="facilities-list">
                <?php if (!empty($approvedFacilities)): ?>
                    <?php foreach ($approvedFacilities as $facility): ?>
                        <div class="form-group">
                            <div class="row g-2 align-items-center mb-3">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input"
                                            id="<?= htmlspecialchars($facility['name']) ?>"
                                            name="facilities[]"
                                            value="<?= htmlspecialchars($facility['name']) ?>">
                                        <label class="form-check-label"
                                            for="<?= htmlspecialchars($facility['name']) ?>">
                                            <?= htmlspecialchars($facility['name']) ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" placeholder="Building or Room Number"
                                        name="building_or_room[<?= htmlspecialchars($facility['name']) ?>]">
                                </div>
                                <div class="col-md-4">
                                    <input type="time" class="form-control" placeholder="Time of Use"
                                        name="time_of_use[<?= htmlspecialchars($facility['name']) ?>]">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No approved facilities found.</p>
                <?php endif; ?>
            </div>


            <!-- Approval Section -->
            <div class="form-section mt-5">
                <h3>Approval</h3>
                <div class="row mb-4 text-center">
                    <div class="col-md-4 mb-4">
                        <label class="form-label">Requested by:</label>
                        <input type="text" class="form-control mb-2" name="requested_by"
                            value="<?= htmlspecialchars($userFullName) ?>" readonly>
                        <input type="text" class="form-control mb-2" name="requested_by_designation"
                            value="<?= htmlspecialchars($userDesignation) ?>" readonly>
                    </div>
                    <div class="col-md-4 mb-4">
                        <label class="form-label">Cleared by:</label>
                        <input type="text" class="form-control mb-2" placeholder="Printed Name & Signature" name="cleared_by">
                        <input type="text" class="form-control" placeholder="Designation" name="cleared_by_designation">
                    </div>
                    <div class="col-md-4 mb-4">
                        <label class="form-label">Approved by:</label>
                        <input type="text" class="form-control mb-2" placeholder="Printed Name & Signature" name="approved_by">
                        <input type="text" class="form-control" placeholder="Designation" name="approved_by_designation">
                    </div>
                </div>

                <div class="row mb-4 text-center">
                    <div class="col-md-6">
                        <label class="form-label">Endorsed by:</label>
                        <input type="text" class="form-control" placeholder="Printed Name & Signature" name="endorsed_by">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Approved by:</label>
                        <input type="text" class="form-control" placeholder="Property Custodian" name="approved_by_pc">
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="form-row mt-4">
                <div class="d-flex justify-content-between">
                    <a class="btn btn-secondary" href="studentActivities.php" role="button">Back</a>
                    <button type="submit" class="btn btn-success mb-3">Submit Proposal</button>
                </div>
            </div>

            <!-- Modal for Pending Requests -->
            <?php if ($hasPendingRequests): ?>
                <div class="modal fade" id="pendingRequestsModal" tabindex="-1" aria-labelledby="pendingRequestsModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="pendingRequestsModalLabel">Pending Block Request</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                You have a pending block request. Please wait until it is approved.
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Modal for No Requests -->
            <?php if ($hasNoRequests): ?>
                <div class="modal fade" id="noRequestsModal" tabindex="-1" aria-labelledby="noRequestsModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="noRequestsModalLabel">No Block Requests Found</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                You have not sent any block requests yet. You will be redirected to the facility request page.
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Include jQuery before the script -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include Popper.js and Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <!-- Custom Script to generate facilities list -->


    <script>
        $(document).ready(function() {
            // Show the appropriate modal based on conditions
            <?php if ($hasPendingRequests): ?>
                $('#pendingRequestsModal').modal('show');
            <?php elseif ($hasNoRequests): ?>
                $('#noRequestsModal').modal('show');
                setTimeout(function() {
                    window.location.href = '../IntelliDocUpdate/booking/facilityBooking.php'; // Redirect after 5 seconds
                }, 5000);
            <?php endif; ?>
        });
    </script>

</body>
</html>

<?php
require_once 'phpqrcode/qrlib.php';
include 'database.php';
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



// Function to fetch the title of the specific activity proposal
function getProposalTitleById($conn, $proposalId)
{
    $sql = "SELECT activity_title 
            FROM activity_proposals 
            WHERE proposal_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $proposalId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['activity_title'] ?? 'No activity title found';
}

// Fetch the activity proposal title using the specific ID
$activityTitle = getProposalTitleById($conn, $proposalId);





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

    // Insert booking details into the database
    $stmt = $conn->prepare("
        INSERT INTO bookings (user_id, club_name, contact_number, purpose, date_of_use)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issss", $userId, $clubName, $contactNumber, $purpose, $dateOfUse);
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

            <div class="form-section">
                <h3>Facilities Requested <small class="text-muted">(Only Approved)</small></h3>
                <div id="facilities-list">
                    <?php if (!empty($approvedFacilities)): ?>
                        <?php foreach ($approvedFacilities as $facility): ?>
                            <div class="form-group">
                                <div class="row g-2 align-items-center mb-3">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input"
                                                id="<?= htmlspecialchars($facility['name'] ?? '') ?>"
                                                name="facilities[]"
                                                value="<?= htmlspecialchars($facility['name'] ?? '') ?>">
                                            <label class="form-check-label"
                                                for="<?= htmlspecialchars($facility['name'] ?? '') ?>">
                                                <?= htmlspecialchars($facility['name'] ?? '') ?>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" placeholder="Building or Room Number"
                                            name="building_or_room[<?= htmlspecialchars($facility['name'] ?? '') ?>]">
                                    </div>
                                    <div class="col-md-4">
                                        <input type="time" class="form-control" placeholder="Time of Use"
                                            name="time_of_use[<?= htmlspecialchars($facility['name'] ?? '') ?>]">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No approved facilities found.</p>
                    <?php endif; ?>
                </div>
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
            const facilities = [
                "Ladouix Hall",
                "Boulay Bldg.",
                "Gymnasium",
                "Miserero Bldg.",
                "Polycarp Bldg.",
                "Coindre Bldg.",
                "Piazza",
                "Xavier Hall",
                "Open Court w/ Lights",
                "ITVET",
                "Nursing Room/Hall",
                "Power Campus",
                "Camp Raymond Bldg.",
                "Norbert Bldg.",
                "H.E Hall",
                "Atrium"
            ];

            const facilitiesListDiv = $('#facilities-list');

            facilities.forEach(function(facility, index) {
                // Generate unique IDs and names
                const facilityId = 'facility-' + index;
                const sanitizedFacility = facility.replace(/\s+/g, '_').replace(/[^\w]/g, '').toLowerCase();
                const buildingName = sanitizedFacility + '_building';
                const timeName = sanitizedFacility + '_time';

                // Create the form group HTML
                const formGroupHTML = `
                        <div class="form-group">
                            <div class="row g-2 align-items-center mb-3">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="${facilityId}" name="facilities[]" value="${facility}">
                                        <label class="form-check-label" for="${facilityId}">${facility}</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" placeholder="Building or Room Number" name="${buildingName}">
                                </div>
                                <div class="col-md-4">
                                    <input type="time" class="form-control" placeholder="Time of Use" name="${timeName}">
                                </div>
                            </div>
                        </div>
                    `;
                // Append the form group to the facilities list div
                facilitiesListDiv.append(formGroupHTML);
            });
        });
    </script>
</body>

</html>
<?php
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

// Fetch the contact number
$userContact = getUserContact($conn, $userId);


// Ensure user ID is set in the session
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'title' => 'Error', 'message' => 'You must be logged in to make a block request.']);
    exit();
}

// Get the proposal ID from the URL
$proposalId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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


// Fetch the club data dynamically
$clubData = getClubData($conn, $userId);

if (!$clubData) {
    echo json_encode(['success' => false, 'title' => 'Error', 'message' => 'No club membership found for the logged-in user.']);
    exit();
}

$clubId = $clubData['club_id']; // Get the club ID from the fetched data
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

            <!-- Facilities Requested -->
            <div class="form-section">
                <h3>Facilities Requested <small class="text-muted">(Please check)</small></h3>
                <div id="facilities-list"></div>
                <!-- 'Others' Option -->
                <div class="form-group">
                    <div class="row g-2 align-items-center mb-3">
                        <div class="col-auto">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="others" name="facilities[]" value="Others">
                                <label class="form-check-label" for="others">Others</label>
                            </div>
                        </div>
                        <div class="col">
                            <input type="text" class="form-control" placeholder="Specify Other Facility" name="others_building">
                        </div>
                        <div class="col">
                            <input type="time" class="form-control" placeholder="Time of Use" name="others_time">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Approval Section -->
            <div class="form-section mt-5">
                <h3>Approval</h3>
                <div class="row mb-4 text-center">
                    <div class="col-md-4 mb-4">
                        <label class="form-label">Requested by:</label>
                        <input type="text" class="form-control mb-2" placeholder="Printed Name & Signature" name="requested_by">
                        <input type="text" class="form-control" placeholder="Designation" name="requested_by_designation">
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
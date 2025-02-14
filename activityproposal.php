<?php
// Start a secure session
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

include 'system_log/activity_log.php';
include 'database.php';
include 'phpqrcode/qrlib.php';

$username = $_SESSION['username'];
$userActivity = 'User visited Activity Proposal Form';
logActivity($username, $userActivity);

// Fetch facilities from the database
$facilities = [];
$facilityQuery = "SELECT id, name FROM facilities";
if ($result = $conn->query($facilityQuery)) {
    while ($row = $result->fetch_assoc()) {
        $facilities[] = $row;
    }
    $result->free();
} else {
    die("Error fetching facilities: " . $conn->error);
}

// Helper functions
function setValue($data)
{
    return isset($data) && !empty($data) ? htmlspecialchars($data) : '';
}
function setReadonly($data)
{
    return isset($data) && !empty($data) ? 'readonly' : '';
}
function sanitize_input($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

// Prevent unauthorized includes
$allowed_includes = ['clientnavbar.php'];
if (!in_array('clientnavbar.php', $allowed_includes)) {
    die('Unauthorized file inclusion');
}

// Functions to fetch club, applicant, moderator, and dean data
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
function getApplicantDetails($conn, $user_id)
{
    $sql = "SELECT full_name AS applicant_name, contact AS applicant_contact FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?: ['applicant_name' => '', 'applicant_contact' => ''];
}
function getModeratorData($conn, $club_id)
{
    $sql = "SELECT u.full_name AS moderator_name, cm.designation 
            FROM club_memberships cm 
            JOIN users u ON cm.user_id = u.id 
            WHERE cm.club_id = ? AND cm.designation = 'moderator'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $club_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?: ['moderator_name' => '', 'designation' => ''];
}
function getDeanData($conn, $club_id)
{
    $sql = "SELECT u.full_name AS dean_name
            FROM club_memberships cm
            JOIN users u ON cm.user_id = u.id
            WHERE cm.club_id = ? AND cm.designation = 'dean'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $club_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?: ['dean_name' => ''];
}

// Fetch club and applicant details
$user_id = $_SESSION['user_id'];
$club_data = getClubData($conn, $user_id);
$applicant_details = getApplicantDetails($conn, $user_id);
$applicant_name = $applicant_details['applicant_name'];
$applicant_contact = $applicant_details['applicant_contact'];
$moderator_data = isset($club_data['club_id']) ? getModeratorData($conn, $club_data['club_id']) : ['moderator_name' => '', 'designation' => ''];
$moderator_name = $moderator_data['moderator_name'];
$dean_data = isset($club_data['club_id']) ? getDeanData($conn, $club_data['club_id']) : ['dean_name' => ''];
$dean_name = $dean_data['dean_name'];

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }

    // Collect data from form submission or preloaded values
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
    $start_time = sanitize_input($_POST['startTime']);
    $end_time = sanitize_input($_POST['endTime']);
    $target_participants = sanitize_input($_POST['targetParticipants']);
    $expected_participants = (int)sanitize_input($_POST['expectedParticipants']);

    // Signatures & contact info
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

    // Default values for status and rejection_reason
    $status = "Received";
    $rejection_reason = null;

    // Insert into activity_proposals
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
        ) 
        VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");
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
        $applicant_contact,
        $moderator_name,
        $moderator_signature,
        null, // moderator_date_signed
        null, // moderator_contact
        $faculty_signature,
        $faculty_contact,
        $dean_name,
        $dean_signature,
        $status,
        $rejection_reason
    );

    // Handle BLOB fields
    if ($moderator_signature !== null) {
        $stmt->send_long_data(23, $moderator_signature);
    }
    if ($dean_signature !== null) {
        $stmt->send_long_data(29, $dean_signature);
    }

    if ($stmt->execute()) {
        $proposal_id = $stmt->insert_id;

        // (Optional) Generate a QR code for applicant signature
        $qrData = "http://10.6.8.72/main/IntelliDocM/verify_qr/verify_qr.php?proposal_id=" . urlencode($proposal_id) . "&signed_by=" . urlencode($applicant_name);
        $qrDirectory = "client_qr_codes";
        if (!is_dir($qrDirectory)) {
            if (!mkdir($qrDirectory, 0777, true) && !is_dir($qrDirectory)) {
                die("Failed to create QR code directory: $qrDirectory");
            }
        }
        $qrFilePath = $qrDirectory . "/applicant_qr_" . $proposal_id . ".png";
        QRcode::png($qrData, $qrFilePath, QR_ECLEVEL_L, 5);

        // Update the applicant_signature column with the QR code path
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Activity Proposal Form</title>
    <link href="css/act_Pro.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

    <script>
        // Log activity before submission
        function logSubmitProposal() {
            const userActivity = 'User submitted the Activity Proposal Form';
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "system_log/log_activity.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    console.log('Activity logged successfully.');
                }
            };
            xhr.send("activity=" + encodeURIComponent(userActivity));
        }
    </script>
</head>

<body>
    <div class="container my-5">
        <a class="btn btn-secondary mb-3" href="client.php">← Back</a>
        <!-- Overlay Box -->
        <div class="overlay-box">
            <p><strong>Index No.:</strong> <u>7.3</u></p>
            <p><strong>Revision No.:</strong> <u>00</u></p>
            <p><strong>Effective Date:</strong> <u>05/16/24</u></p>
            <p><strong>Control No.:</strong> ___________</p>
        </div>

        <div class="header-content">
            <img src="images/cjc logo.jpg" alt="Logo" class="header-logo">
            <div class="header-text">
                <h2 class="text-center text-uppercase">Cor Jesu College, Inc.</h2>
                <div class="line yellow-line"></div>
                <div class="line blue-line"></div>
                <p class="text-center">Sacred Heart Avenue, Digos City, Province of Davao del Sur, Philippines</p>
                <p class="text-center">Tel. No.: (082) 553-2433 local 101 • Fax No.: (082) 553-2333 • www.cjc.edu.ph</p>
            </div>
        </div>

        <h3 class="text-center">ACTIVITY PROPOSAL FORM</h3>

        <form method="POST" action="" id="submitProposalForm" enctype="multipart/form-data">
            <!-- CSRF token -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <!-- Organization Details -->
            <div class="mb-4">
                <label for="organizationName" class="form-label">Name of the Organization/ Class/ College:</label>
                <input type="text" class="form-control" id="organizationName" name="organizationName"
                    value="<?php echo setValue($club_data['club_name']); ?>" <?php echo setReadonly($club_data['club_name']); ?> />
            </div>
            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="acronym" class="form-label">Acronym:</label>
                    <input type="text" class="form-control" id="acronym" name="acronym"
                        value="<?php echo setValue($club_data['acronym']); ?>" <?php echo setReadonly($club_data['acronym']); ?> />
                </div>
                <div class="col-md-6">
                    <label class="form-label">Organization Category:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="academic" name="clubType" value="Academic"
                            <?php echo ($club_data['club_type'] === 'Academic') ? 'checked disabled' : 'disabled'; ?>>
                        <label class="form-check-label" for="academic">Academic</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="nonAcademic" name="clubType" value="Non-Academic"
                            <?php echo ($club_data['club_type'] === 'Non-Academic') ? 'checked disabled' : 'disabled'; ?>>
                        <label class="form-check-label" for="nonAcademic">Non-Academic</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="acco" name="clubType" value="ACCO"
                            <?php echo ($club_data['club_type'] === 'ACCO') ? 'checked disabled' : 'disabled'; ?>>
                        <label class="form-check-label" for="acco">ACCO</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="csg" name="clubType" value="CSG"
                            <?php echo ($club_data['club_type'] === 'CSG') ? 'checked disabled' : 'disabled'; ?>>
                        <label class="form-check-label" for="csg">CSG</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="collegeLGU" name="clubType" value="College-LGU"
                            <?php echo ($club_data['club_type'] === 'College-LGU') ? 'checked disabled' : 'disabled'; ?>>
                        <label class="form-check-label" for="collegeLGU">College-LGU</label>
                    </div>
                </div>
            </div>

            <!-- Activity Title and Type -->
            <div class="row mb-4">
                <div class="col mb-6">
                    <label for="activityTitle" class="form-label">Title of the Activity:</label>
                    <input type="text" class="form-control" id="activityTitle" name="activityTitle" placeholder="Enter activity title" />
                </div>
                <div class="col mb-6">
                    <label class="form-label">Type of Activity:</label>
                    <div class="form-check">
                        <input class="form-check-input activity-type" type="checkbox" id="on-campus" name="activityType[]" value="On-Campus Activity">
                        <label class="form-check-label" for="on-campus">On-Campus Activity</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input activity-type" type="checkbox" id="off-campus" name="activityType[]" value="Off-Campus Activity">
                        <label class="form-check-label" for="off-campus">Off-Campus Activity</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input activity-type" type="checkbox" id="online" name="activityType[]" value="Online Activity">
                        <label class="form-check-label" for="online">Online Activity</label>
                    </div>
                </div>
            </div>

            <!-- Objectives -->
            <div class="mb-4">
                <label class="form-label">Objectives:</label>
                <textarea class="form-control" rows="3" id="objectives" name="objectives" placeholder="List objectives here"></textarea>
            </div>

            <!-- Program Category -->
            <div class="mb-4">
                <label class="form-label">Student Development Program Category:</label>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="omp" name="omp" value="OMP">
                            <label class="form-check-label" for="omp">Organizational Management Development (OMP)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="ksd" name="ksd" value="KSD">
                            <label class="form-check-label" for="ksd">Knowledge & Skills Development (KSD)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="ct" name="ct" value="CT">
                            <label class="form-check-label" for="ct">Capacity and Teambuilding (CT)</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="srf" name="srf" value="SRF">
                            <label class="form-check-label" for="srf">Spiritual & Religious Formation (SRF)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="rpInitiative" name="rpInitiative" value="RPI">
                            <label class="form-check-label" for="rpInitiative">Research & Project Initiative (RPI)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="cesa" name="cesa" value="CESA">
                            <label class="form-check-label" for="cesa">Community Engagement & Social Advocacy (CESA)</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Facility Bookings (Improved UI) -->
            <h4 class="mb-3">Facility Bookings (Optional)</h4>
            <div id="facilityBookingsContainer">
                <!-- First (default) booking block -->
                <div class="card mb-3 facility-booking" data-index="0">
                    <div class="card-body">
                        <h5 class="card-title">Facility Booking #<span class="booking-number">1</span></h5>

                        <!-- Facility Selection -->
                        <div class="mb-3">
                            <label for="facilitySelect_0" class="form-label fw-bold">Select Facility:</label>
                            <select class="form-select" id="facilitySelect_0" name="facilityBookings[0][facility]">
                                <option value="">-- Select Facility --</option>
                                <?php foreach ($facilities as $facility): ?>
                                    <option value="<?php echo $facility['id']; ?>">
                                        <?php echo htmlspecialchars($facility['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Time Slots -->
                        <div class="time-slots" data-index="0">
                            <!-- One time slot row -->
                            <div class="row g-2 align-items-end time-slot" data-index="0">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Date:</label>
                                    <input type="date" class="form-control" name="facilityBookings[0][slots][0][date]">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Start Time:</label>
                                    <input type="time" class="form-control" name="facilityBookings[0][slots][0][start]">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">End Time:</label>
                                    <input type="time" class="form-control" name="facilityBookings[0][slots][0][end]">
                                </div>
                                <div class="col-md-3 text-end">
                                    <button type="button" class="removeSlot btn btn-danger btn-sm mt-4">Remove Slot</button>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="addSlot btn btn-secondary btn-sm mt-3" data-index="0">
                            Add Time Slot
                        </button>
                    </div>
                    <div class="card-footer text-end">
                        <button type="button" class="removeBooking btn btn-outline-danger btn-sm">
                            Remove Facility Booking
                        </button>
                    </div>
                </div>
            </div>

            <button type="button" id="addBooking" class="btn btn-primary mb-4">Add Another Facility Booking</button>

            <!-- Venue and Time -->
            <div class="row mb-4" id="venue-address-container">
                <div class="col-md-6">
                    <label for="venue" class="form-label">Venue of the Activity:</label>
                    <input type="text" class="form-control" id="venue" name="venue" placeholder="Enter venue" />
                </div>
                <div class="col-md-6">
                    <label for="address" class="form-label">Address of the Venue:</label>
                    <input type="text" class="form-control" id="address" name="address" placeholder="Enter address" />
                </div>
            </div>

            <div class="row mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <label for="start-date" class="form-label">Start Date of the Activity:</label>
                        <input type="date" class="form-control" id="start-date" name="start_date" />
                    </div>
                    <div class="col-md-6">
                        <label for="end-date" class="form-label">End Date of the Activity:</label>
                        <input type="date" class="form-control" id="end-date" name="end_date" />
                    </div>
                </div>
                <div class="col-md-3 mt-3">
                    <label for="startTime" class="form-label">Starting Time:</label>
                    <input type="time" class="form-control" id="startTime" name="startTime" />
                </div>
                <div class="col-md-3 mt-3">
                    <label for="endTime" class="form-label">Finishing Time:</label>
                    <input type="time" class="form-control" id="endTime" name="endTime" />
                </div>
            </div>

            <!-- Participants -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="targetParticipants" class="form-label">Target Participants:</label>
                    <input type="text" class="form-control" id="targetParticipants" name="targetParticipants" placeholder="Enter target participants" />
                </div>
                <div class="col-md-6">
                    <label for="expectedParticipants" class="form-label">Expected Number of Participants:</label>
                    <input type="number" class="form-control" id="expectedParticipants" name="expectedParticipants" placeholder="Enter expected number" />
                </div>
            </div>

            <!-- Signatures -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label">Applicant</label>
                    <input type="text" class="form-control mb-2" name="applicantName" placeholder="Applicant Name"
                        value="<?php echo setValue($applicant_name); ?>" <?php echo setReadonly($applicant_name); ?> />
                    <input type="text" class="form-control mb-2" name="applicantDesignation" placeholder="Designation"
                        value="<?php echo setValue($club_data['designation']); ?>" <?php echo setReadonly($club_data['designation']); ?> />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Moderator</label>
                    <input type="text" class="form-control mb-2" name="moderatorName" placeholder="Moderator Name"
                        value="<?php echo setValue($moderator_name); ?>" <?php echo setReadonly($moderator_name); ?> />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Noted by:</label>
                    <input type="text" class="form-control mb-2" name="dean_name" placeholder="College Dean Signature"
                        value="<?php echo setValue($dean_name); ?>" <?php echo setReadonly($dean_name); ?> />
                </div>
            </div>

            <!-- Submit Button -->
            <div class="text-center">
                <button type="submit" class="btn btn-success" onclick="logSubmitProposal()">
                    Submit Proposal
                </button>
            </div>
        </form>
    </div>

    <!-- JavaScript for dynamic Facility Booking Blocks and Time Slots -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let bookingIndex = 0; // index for facility booking blocks

            // Add a new facility booking block (card-based layout)
            document.getElementById("addBooking").addEventListener("click", function() {
                bookingIndex++;
                const container = document.getElementById("facilityBookingsContainer");
                const blockDiv = document.createElement("div");
                blockDiv.classList.add("card", "mb-3", "facility-booking");
                blockDiv.dataset.index = bookingIndex;

                blockDiv.innerHTML = `
          <div class="card-body">
            <h5 class="card-title">Facility Booking #<span class="booking-number">${bookingIndex + 1}</span></h5>

            <div class="mb-3">
              <label for="facilitySelect_${bookingIndex}" class="form-label fw-bold">Select Facility:</label>
              <select class="form-select" id="facilitySelect_${bookingIndex}" name="facilityBookings[${bookingIndex}][facility]">
                <option value="">-- Select Facility --</option>
                <?php foreach ($facilities as $facility): ?>
                  <option value="<?php echo $facility['id']; ?>">
                    <?php echo htmlspecialchars($facility['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="time-slots" data-index="0">
              <div class="row g-2 align-items-end time-slot" data-index="0">
                <div class="col-md-3">
                  <label class="form-label fw-bold">Date:</label>
                  <input type="date" class="form-control" name="facilityBookings[${bookingIndex}][slots][0][date]">
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-bold">Start Time:</label>
                  <input type="time" class="form-control" name="facilityBookings[${bookingIndex}][slots][0][start]">
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-bold">End Time:</label>
                  <input type="time" class="form-control" name="facilityBookings[${bookingIndex}][slots][0][end]">
                </div>
                <div class="col-md-3 text-end">
                  <button type="button" class="removeSlot btn btn-danger btn-sm mt-4">
                    Remove Slot
                  </button>
                </div>
              </div>
            </div>

            <button type="button" class="addSlot btn btn-secondary btn-sm mt-3" data-index="${bookingIndex}">
              Add Time Slot
            </button>
          </div>
          <div class="card-footer text-end">
            <button type="button" class="removeBooking btn btn-outline-danger btn-sm">
              Remove Facility Booking
            </button>
          </div>
        `;

                container.appendChild(blockDiv);
            });

            // Remove a facility booking block
            document.getElementById("facilityBookingsContainer").addEventListener("click", function(e) {
                if (e.target && e.target.classList.contains("removeBooking")) {
                    e.target.closest(".facility-booking").remove();
                }
            });

            // Add a new time slot within a facility booking block
            document.getElementById("facilityBookingsContainer").addEventListener("click", function(e) {
                if (e.target && e.target.classList.contains("addSlot")) {
                    const block = e.target.closest(".facility-booking");
                    const bookingIdx = block.dataset.index;
                    const timeSlotsContainer = block.querySelector(".time-slots");
                    let slotIndex = timeSlotsContainer.querySelectorAll(".time-slot").length;

                    const slotDiv = document.createElement("div");
                    slotDiv.classList.add("row", "g-2", "align-items-end", "time-slot");
                    slotDiv.dataset.index = slotIndex;

                    slotDiv.innerHTML = `
            <div class="col-md-3">
              <label class="form-label fw-bold">Date:</label>
              <input type="date" class="form-control" name="facilityBookings[${bookingIdx}][slots][${slotIndex}][date]">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Start Time:</label>
              <input type="time" class="form-control" name="facilityBookings[${bookingIdx}][slots][${slotIndex}][start]">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">End Time:</label>
              <input type="time" class="form-control" name="facilityBookings[${bookingIdx}][slots][${slotIndex}][end]">
            </div>
            <div class="col-md-3 text-end">
              <button type="button" class="removeSlot btn btn-danger btn-sm mt-4">
                Remove Slot
              </button>
            </div>
          `;
                    timeSlotsContainer.appendChild(slotDiv);
                }
            });

            // Remove a time slot
            document.getElementById("facilityBookingsContainer").addEventListener("click", function(e) {
                if (e.target && e.target.classList.contains("removeSlot")) {
                    e.target.closest(".time-slot").remove();
                }
            });
        });
    </script>

    <!-- Toggle venue/address fields based on activity type -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const onCampusCheckbox = document.getElementById('on-campus');
            const offCampusCheckbox = document.getElementById('off-campus');
            const venueAddressContainer = document.getElementById('venue-address-container');

            function toggleVenueAddress() {
                if (onCampusCheckbox.checked || offCampusCheckbox.checked) {
                    venueAddressContainer.style.display = 'flex';
                } else {
                    venueAddressContainer.style.display = 'none';
                }
            }
            venueAddressContainer.style.display = 'none';
            onCampusCheckbox.addEventListener('change', toggleVenueAddress);
            offCampusCheckbox.addEventListener('change', toggleVenueAddress);
        });

        // Ensure only one activity type checkbox is selected
        document.addEventListener("DOMContentLoaded", function() {
            const checkboxes = document.querySelectorAll(".activity-type");
            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener("change", function() {
                    checkboxes.forEach((box) => {
                        if (box !== this) {
                            box.checked = false;
                        }
                    });
                });
            });
        });
    </script>

    <!-- Log activity on form submission -->
    <script>
        document.getElementById('submitProposalForm').addEventListener('submit', function(event) {
            event.preventDefault();
            logSubmitProposal();
            this.submit();
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
$conn->close();
?>
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

// Include database connection
include 'database.php';
include 'phpqrcode/qrlib.php';

// Helper function to set the value of a field if data exists
function setValue($data)
{
    return isset($data) && !empty($data) ? htmlspecialchars($data) : '';
}

// Helper function to set fields as readonly if data exists
function setReadonly($data)
{
    return isset($data) && !empty($data) ? 'readonly' : '';
}

// Prevent directory traversal in file includes
$allowed_includes = ['clientnavbar.php'];
if (!in_array('clientnavbar.php', $allowed_includes)) {
    die('Unauthorized file inclusion');
}

// Function to get club data for the logged-in user
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

// Fetch club data for the logged-in user
$user_id = $_SESSION['user_id'];
$club_data = getClubData($conn, $user_id);

// Fetch applicant name
$applicant_details = getApplicantDetails($conn, $user_id);
$applicant_name = $applicant_details['applicant_name'];
$applicant_contact = $applicant_details['applicant_contact'];

// Fetch moderator name and designation
$moderator_data = isset($club_data['club_id']) ? getModeratorData($conn, $club_data['club_id']) : ['moderator_name' => '', 'designation' => ''];
$moderator_name = $moderator_data['moderator_name'];
$moderator_designation = $moderator_data['designation'];

// Fetch dean name
$dean_data = isset($club_data['club_id']) ? getDeanData($conn, $club_data['club_id']) : ['dean_name' => ''];
$dean_name = $dean_data['dean_name'];

// Helper function to sanitize input
function sanitize_input($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

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
    $activity_date = sanitize_input($_POST['date']);
    $start_time = sanitize_input($_POST['startTime']);
    $end_time = sanitize_input($_POST['endTime']);
    $target_participants = sanitize_input($_POST['targetParticipants']);
    $expected_participants = (int)sanitize_input($_POST['expectedParticipants']);
    $applicant_name = $applicant_name;
    $applicant_signature = sanitize_input($_POST['applicantSignature'] ?? '');
    $applicant_designation = sanitize_input($_POST['applicantDesignation']);
    $applicant_date_filed = date('Y-m-d');
    $applicant_contact = $applicant_contact;
    $moderator_name = $moderator_name;
    $moderator_signature = null;
    if (isset($_FILES['moderatorSignature']) && is_uploaded_file($_FILES['moderatorSignature']['tmp_name'])) {
        $moderator_signature = file_get_contents($_FILES['moderatorSignature']['tmp_name']);
    }
    $moderator_date_signed = null;
    $moderator_contact = null;
    $faculty_signature = sanitize_input($_POST['facultySignature']);
    $faculty_contact = sanitize_input($_POST['facultyContact']);
    $dean_name = $dean_name;
    $dean_signature = null;
    if (isset($_FILES['deanSignature']) && is_uploaded_file($_FILES['deanSignature']['tmp_name'])) {
        $dean_signature = file_get_contents($_FILES['deanSignature']['tmp_name']);
    }

    // Default values for status and rejection_reason
    $status = "Received";
    $rejection_reason = null;

    // Corrected INSERT statement with matching columns and placeholders
    $stmt = $conn->prepare("
        INSERT INTO activity_proposals (
            user_id, club_name, acronym, club_type, designation, activity_title, 
            activity_type, objectives, program_category, venue, address, 
            activity_date, start_time, end_time, target_participants, 
            expected_participants, applicant_name, applicant_signature, 
            applicant_designation, applicant_date_filed, applicant_contact, 
            moderator_name, moderator_signature, moderator_date_signed, 
            moderator_contact, faculty_signature, faculty_contact, 
            dean_name, dean_signature, status, rejection_reason
        ) 
        VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");

    // Corrected type string and variable list
    $stmt->bind_param(
        "issssssssssssssissssssbsssssbss",
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
        $moderator_date_signed,
        $moderator_contact,
        $faculty_signature,
        $faculty_contact,
        $dean_name,
        $dean_signature,
        $status,
        $rejection_reason
    );

    // Send blob data using send_long_data()
    // Adjust indices if necessary (zero-based indexing)
    if ($moderator_signature !== null) {
        $stmt->send_long_data(22, $moderator_signature); // Index 22
    }
    if ($dean_signature !== null) {
        $stmt->send_long_data(28, $dean_signature); // Index 28
    }

    if ($stmt->execute()) {
        // Get the ID of the newly inserted proposal
        $proposal_id = $stmt->insert_id;

        // Generate QR Code for Applicant Signature
        $qrData = "http://192.168.0.106/main/IntelliDocM/verify_qr/verify_qr.php?proposal_id=" . urlencode($proposal_id) . "&signed_by=" . urlencode($applicant_name);


        // Define the directory to save QR codes
        $qrDirectory = "client_qr_codes";
        if (!is_dir($qrDirectory)) {
            if (!mkdir($qrDirectory, 0777, true) && !is_dir($qrDirectory)) {
                die("Failed to create QR code directory: $qrDirectory");
            }
        }

        // Define the file path for the QR code
        $qrFilePath = $qrDirectory . "/applicant_qr_" . $proposal_id . ".png";

        // Generate and save the QR code as a file
        QRcode::png($qrData, $qrFilePath, QR_ECLEVEL_L, 5);

        // Update the applicant_signature column with the QR code path
        $updateSql = "UPDATE activity_proposals SET applicant_signature = ? WHERE proposal_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("si", $qrFilePath, $proposal_id);

        if ($updateStmt->execute()) {
            echo "<script>alert('Proposal submitted and QR code for applicant generated successfully!')</script>";
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
    <script></script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Activity Proposal Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f8f9fa;
        }

        .form-control[readonly],
        .form-check-input[disabled] {
            background-color: #e9ecef;
            color: #6c757d;
        }
    </style>
</head>

<body>

    <!-- Include Navbar -->
    <?php include 'includes/clientnavbar.php'; ?>

    <!-- Include Sidebar -->
    <div class="container my-5">
        <h2 class="text-center">ACTIVITY PROPOSAL FORM</h2>
        <form method="POST" action="">
            <!-- Add CSRF token -->
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

            <!-- Activity Title -->


            <div class="row mb-4">
                <div class="col mb-6">
                    <label for="activityTitle" class="form-label">Title of the Activity:</label>
                    <input type="text" class="form-control" id="activityTitle" name="activityTitle" placeholder="Enter activity title" />
                </div>
                <div class="col mb-6">
                    <label class="form-label">Type of Activity:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="on-campus" name="activityType[]" value="On-Campus Activity">
                        <label class="form-check-label" for="on-campus">On-Campus Activity</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="off-campus" name="activityType[]" value="Off-Campus Activity">
                        <label class="form-check-label" for="off-campus">Off-Campus Activity</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="online" name="activityType[]" value="Online Activity">
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
                        <input type="text" class="form-control mt-2" name="other_program" placeholder="Others (Please specify)">
                    </div>
                </div>
            </div>

            <!-- Venue and Time -->
            <div class="row mb-4">
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
                <div class="col-md-4">
                    <label for="date" class="form-label">Date of the Activity:</label>
                    <input type="date" class="form-control" id="date" name="date" />
                </div>
                <div class="col-md-4">
                    <label for="startTime" class="form-label">Starting Time:</label>
                    <input type="time" class="form-control" id="startTime" name="startTime" />
                </div>
                <div class="col-md-4">
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
                    <label class="form-label">Other Faculty/Staff</label>
                    <input type="text" class="form-control mb-2" name="facultySignature" placeholder="Signature Over Printed Name" />
                    <input type="text" class="form-control mb-2" name="facultyContact" placeholder="Contact Number" />
                </div>
            </div>


            <div class="text-center">
                <label class="form-label">Noted by:</label>
                <input type="text" class="form-control mb-2" name="dean_name" placeholder="College Dean Signature Over Printed Name"
                    value="<?php echo setValue($dean_name); ?>" <?php echo setReadonly($dean_name); ?> />
            </div>

            <!-- Submit Button -->
            <div class="text-center">
                <button type="submit" class="btn btn-primary">Submit Proposal</button>
            </div>

        </form>
        <a class="btn btn-success mb-3" href="/main/IntelliDocM/booking/booking.php">Booking Form</a>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
$conn->close();
?>
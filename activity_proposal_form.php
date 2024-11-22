<?php
session_start();
if ($_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit();
}
include 'database.php';

$user_id = $_SESSION['user_id'];

// Function to get club data for the logged-in user
function getClubData($conn, $user_id)
{
    $sql = "SELECT c.club_name, c.acronym, c.club_type, cm.designation
            FROM clubs c
            JOIN club_memberships cm ON c.club_id = cm.club_id
            WHERE cm.user_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Fetch club data for the logged-in user
$club_data = getClubData($conn, $user_id);

// Helper function to set fields as readonly if data exists
function setReadonly($data)
{
    return isset($data) && !empty($data) ? 'readonly' : '';
}

// Helper function to set the value of a field if data exists
function setValue($data)
{
    return isset($data) && !empty($data) ? htmlspecialchars($data) : '';
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect data from form submission or preloaded values
    $club_name = isset($club_data['club_name']) ? $club_data['club_name'] : $_POST['organizationName'];
    $acronym = isset($club_data['acronym']) ? $club_data['acronym'] : $_POST['acronym'];
    $club_type = isset($club_data['club_type']) ? $club_data['club_type'] : $_POST['clubType'];
    $designation = isset($club_data['designation']) ? $club_data['designation'] : $_POST['designation'];
    $activity_title = $_POST['activityTitle'];
    $activity_type = isset($_POST['activityType']) ? implode(", ", $_POST['activityType']) : ""; // Convert activity type array to string
    $objectives = $_POST['objectives'];
    $program_category = implode(", ", array_filter([
        $_POST['omp'] ?? null,
        $_POST['ksd'] ?? null,
        $_POST['ct'] ?? null,
        $_POST['srf'] ?? null,
        $_POST['rpInitiative'] ?? null,
        $_POST['cesa'] ?? null,
        $_POST['other_program'] ?? null
    ]));
    $venue = $_POST['venue'];
    $address = $_POST['address'];
    $activity_date = $_POST['date'];
    $start_time = $_POST['startTime'];
    $end_time = $_POST['endTime'];
    $target_participants = $_POST['targetParticipants'];
    $expected_participants = $_POST['expectedParticipants'];
    $applicant_signature = $_POST['applicantSignature'];
    $applicant_designation = $_POST['applicantDesignation'];
    $applicant_date_filed = $_POST['applicantDateFiled'];
    $applicant_contact = $_POST['applicantContact'];
    $moderator_signature = $_POST['moderatorSignature'];
    $moderator_date_signed = $_POST['moderatorDateSigned'];
    $moderator_contact = $_POST['moderatorContact'];
    $faculty_signature = $_POST['facultySignature'];
    $faculty_contact = $_POST['facultyContact'];
    $dean_signature = $_POST['deanSignature'];

    // Default values for status and rejection_reason
    $status = "Received";
    $rejection_reason = null;

    // Insert data into activity_proposals table
    $stmt = $conn->prepare("INSERT INTO activity_proposals 
        (user_id, club_name, acronym, club_type, designation, activity_title, activity_type, objectives, program_category, venue, address, activity_date, start_time, end_time, target_participants, expected_participants, applicant_signature, applicant_designation, applicant_date_filed, applicant_contact, moderator_signature, moderator_date_signed, moderator_contact, faculty_signature, faculty_contact, dean_signature, status, rejection_reason)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "issssssssssssssissssssssssss",
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
        $applicant_signature,
        $applicant_designation,
        $applicant_date_filed,
        $applicant_contact,
        $moderator_signature,
        $moderator_date_signed,
        $moderator_contact,
        $faculty_signature,
        $faculty_contact,
        $dean_signature,
        $status,
        $rejection_reason
    );

    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Proposal submitted successfully!</div>";
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
    <link href="css/styles.css" rel="stylesheet">
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
    <hr>

    <hr>
    <div class="container my-5">
        <h2 class="text-center">ACTIVITY PROPOSAL FORM</h2>
        <form method="POST" action="">
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
                            <?php echo ($club_data['club_type'] === 'Academic') ? 'checked disabled' : ''; ?>>
                        <label class="form-check-label" for="academic">Academic</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="nonAcademic" name="clubType" value="Non-Academic"
                            <?php echo ($club_data['club_type'] === 'Non-Academic') ? 'checked disabled' : ''; ?>>
                        <label class="form-check-label" for="nonAcademic">Non-Academic</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="acco" name="clubType" value="ACCO"
                            <?php echo ($club_data['club_type'] === 'ACCO') ? 'checked disabled' : ''; ?>>
                        <label class="form-check-label" for="acco">ACCO</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="csg" name="clubType" value="CSG"
                            <?php echo ($club_data['club_type'] === 'CSG') ? 'checked disabled' : ''; ?>>
                        <label class="form-check-label" for="csg">CSG</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="collegeLGU" name="clubType" value="College-LGU"
                            <?php echo ($club_data['club_type'] === 'College-LGU') ? 'checked disabled' : ''; ?>>
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
                    <input type="text" class="form-control mb-2" name="applicantSignature" placeholder="Signature Over Printed Name" />
                    <input type="text" class="form-control mb-2" name="applicantDesignation" placeholder="Designation" />
                    <input type="date" class="form-control mb-2" name="applicantDateFiled" placeholder="Date Filed" />
                    <input type="text" class="form-control mb-2" name="applicantContact" placeholder="Contact Number" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Moderator</label>
                    <input type="text" class="form-control mb-2" name="moderatorSignature" placeholder="Signature Over Printed Name" />
                    <input type="date" class="form-control mb-2" name="moderatorDateSigned" placeholder="Date Signed" />
                    <input type="text" class="form-control mb-2" name="moderatorContact" placeholder="Contact Number" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Other Faculty/Staff</label>
                    <input type="text" class="form-control mb-2" name="facultySignature" placeholder="Signature Over Printed Name" />
                    <input type="text" class="form-control mb-2" name="facultyContact" placeholder="Contact Number" />
                </div>
            </div>

            <div class="text-center">
                <label class="form-label">Noted by:</label>
                <input type="text" class="form-control mb-2" name="deanSignature" placeholder="College Dean Signature Over Printed Name" />
            </div>

            <!-- Submit Button -->
            <div class="text-center">
                <button type="submit" class="btn btn-primary">Submit Proposal</button>
            </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
$conn->close();
?>
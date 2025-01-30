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

    // If no CSRF token is available in the session, generate one.
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));  // CSRF token generation
    }


    $username = $_SESSION['username'];
    $userActivity = 'User visited Booking Form Page';
    logActivity($username, $userActivity);

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

    // Function to fetch user's designation
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

    // Fetch full name
    function getUserFullName($conn, $user_id)
    {
        $sql = "SELECT full_name FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['full_name'] ?? '';
    }

    $userDesignation = getUserDesignation($conn, $userId);
    $userContact = getUserContact($conn, $userId);
    $userFullName = getUserFullName($conn, $userId);

    // Ensure user ID is set in the session
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'title' => 'Error', 'message' => 'You must be logged in to make a block request.']);
        exit();
    }


    // -- 1. Unify reading of proposal_id (check POST first, else fallback to GET) --
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // If the form was submitted, we expect to get proposal_id from POST
        $proposalId = isset($_POST['proposal_id']) ? (int)$_POST['proposal_id'] : 0;
    } else {
        // If just coming via the link, we get it from the query string
        $proposalId = isset($_GET['proposal_id']) ? (int)$_GET['proposal_id'] : 0;
    }

    // Validate
    if ($proposalId <= 0) {
        exit("Invalid proposal ID.");
    }

    // -- 2. Fetch the proposal record once we have a valid proposalId --
    $conn = getDbConnection();
    $sql = "SELECT * FROM activity_proposals WHERE proposal_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $proposalId);
    $stmt->execute();
    $result = $stmt->get_result();
    $proposal = $result->fetch_assoc();
    $stmt->close();

    if (!$proposal) {
        exit("Proposal not found for ID: $proposalId");
    }



    $clubData = getClubData($conn, $userId);

    if (!$clubData) {
        echo json_encode(['success' => false, 'title' => 'Error', 'message' => 'No club membership found for the logged-in user.']);
        exit();
    }

    $clubId = $clubData['club_id']; // Get the club ID

    // Function to fetch approved facilities
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

    // Check for pending requests
    function checkPendingRequests($conn, $club_id)
    {
        $sql = "SELECT id FROM block_requests WHERE club_id = ? AND status = 'Pending'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $club_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    $hasPendingRequests = checkPendingRequests($conn, $clubId);

    // Function to get pending requests with facilities
    function getPendingRequests($conn, $clubId)
    {
        $sql = "SELECT br.id, f.name AS facility_name, br.date
                FROM block_requests br
                JOIN facilities f ON br.facility_id = f.id
                WHERE br.club_id = ? AND br.status = 'Pending'";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $clubId);
        $stmt->execute();
        $result = $stmt->get_result();

        $pendingRequests = [];
        while ($row = $result->fetch_assoc()) {
            $pendingRequests[] = $row;
        }

        return $pendingRequests;
    }

    // Fetch pending requests
    $pendingRequests = getPendingRequests($conn, $clubId);
    $hasNoRequests = empty($approvedFacilities);

    // Function to get the title of a specific activity proposal
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

    $activityTitle = getProposalTitleById($conn, $proposalId);

    // Function to get facility ID based on the name
    function getFacilityIdByName($facilityName)
    {
        global $conn;
        $sql = "SELECT id FROM facilities WHERE name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $facilityName);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($facilityId);
        $stmt->fetch();
        return $facilityId;
    }

    // Function to get booking date for a specific user and club
    function getBookingDate($conn, $userId, $clubId)
    {
        $sql = "SELECT br.date 
                FROM block_requests br
                JOIN club_memberships cm ON br.club_id = cm.club_id
                WHERE cm.user_id = ? AND br.club_id = ? AND br.status = 'Approved'";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $clubId);
        $stmt->execute();
        $result = $stmt->get_result();

        // Return booking date if found, else return empty string
        if ($row = $result->fetch_assoc()) {
            return $row['date'];
        }
        return '';
    }

    $bookingDate = getBookingDate($conn, $userId, $clubId);

    // Collect form data
    $clubName = $_POST['organization_nature'] ?? '';
    $contactNumber = $_POST['contact_number'] ?? '';
    $purposeRequest = $_POST['purpose_request'] ?? '';
    $requestedBy = $_POST['requested_by'] ?? '';
    $requestedByDesignation = $_POST['requested_by_designation'] ?? '';

    // Handle facilities and their details
    $facilities = $_POST['facilities'] ?? [];
    $buildingOrRoom = $_POST['building_or_room'] ?? [];
    $dateOfUse = $_POST['date_of_use'] ?? [];
    $timeOfUse = $_POST['time_of_use'] ?? [];
    $endTimeOfUse = $_POST['end_time_of_use'] ?? [];


    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            die('Invalid CSRF token');
        }

        // Ensure form data is not empty
        if (empty($clubName) || empty($contactNumber) || empty($purposeRequest)) {
            echo json_encode(['success' => false, 'message' => 'All required fields must be filled out.']);
            exit();
        }

        // Insert booking details
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, club_name, contact_number, purpose, proposal_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $userId, $clubName, $contactNumber, $purposeRequest, $proposalId);

        if ($stmt->execute()) {
            $bookingId = $stmt->insert_id;

            // Generate QR code for requested_by_signature (Only for the requesting party)
            $qrData = "http://localhost/main/IntelliDocM/verify_qr/verify_booking.php?booking_id=" . urlencode($bookingId);
            $qrDirectory = "client_qr_codes";
            if (!is_dir($qrDirectory)) {
                mkdir($qrDirectory, 0777, true);
            }

            $requestedByQrFilePath = $qrDirectory . "/requested_by_qr_" . $bookingId . ".png";
            QRcode::png($qrData, $requestedByQrFilePath, QR_ECLEVEL_L, 5);

            $updateStmt = $conn->prepare("UPDATE bookings SET requested_by_signature = ? WHERE id = ?");
            $updateStmt->bind_param("si", $requestedByQrFilePath, $bookingId);
            $updateStmt->execute();

            // Handle facilities data (if any)
            if (!empty($facilities)) {
                $facilityStmt = $conn->prepare("INSERT INTO booked_facilities (booking_id, facility_id, building_or_room, date_of_use, time_of_use, end_time_of_use) VALUES (?, ?, ?, ?, ?, ?)");

                foreach ($facilities as $facilityName) {
                    $facilityId = getFacilityIdByName($facilityName);
                    $building = $buildingOrRoom[$facilityName] ?? '';
                    $time = $timeOfUse[$facilityName] ?? '';
                    $endTime = $endTimeOfUse[$facilityName] ?? '';

                    $facilityStmt->bind_param("iissss", $bookingId, $facilityId, $building, $dateOfUse, $time, $endTime);
                    $facilityStmt->execute();
                }

                $facilityStmt->close();
            }

            echo "<script>alert('Booking successfully submitted! QR code for the requesting party generated.')</script>";
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
        <script>
            // This function logs the activity when the "Submit Request" button is clicked
            function logSubmitRequest() {
                const userActivity = 'User submitted a Facility Booking Request'; // Activity description

                // Send an AJAX request to log the activity
                logActivity(userActivity);
            }

            // Common logActivity function that sends the log to the server via AJAX
            function logActivity(userActivity) {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "system_log/log_activity.php", true); // Ensure this path is correct
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        console.log('Activity logged successfully.'); // Optional: you can handle the response here
                    }
                };
                xhr.send("activity=" + encodeURIComponent(userActivity)); // Send the activity log to the server
            }

            // Attach event listener to the external button
            document.getElementById('submitButton').addEventListener('click', function(event) {
                // Prevent the form submission from happening immediately
                logSubmitRequest(); // Log the activity first

                // Submit the form programmatically after logging
                document.getElementById('facilityBookingForm').submit(); // Assuming the form has an id of 'facilityBookingForm'
            });
        </script>

    </head>

    <body>
    <header>
        <?php include 'includes/clientnavbar.php'; ?>
    </header>

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

            <form method="POST" action="boking.php" id="facilityBookingForm">

                <input type="hidden" name="proposal_id" value="<?= htmlspecialchars($proposalId) ?>">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">


                <!-- Requesting Party Information -->
                <div class="form-group   mb-4">
                    <h3>Requesting Party</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nature of Department/Group/Organization</label>
                            <input type="text" class="form-control" name="organization_nature" value="<?= htmlspecialchars($clubData['club_name']) ?>" readonly>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label">Contact Number</label>
                            <input type="text" class="form-control" name="contact_number" value="<?= htmlspecialchars($userContact) ?>" readonly>
                        </div>
                        <div class="form-group col-md-12">
                            <label class="form-label">Purpose of Request</label>
                            <input type="text" class="form-control" name="purpose_request" value="<?= htmlspecialchars($activityTitle) ?>">
                        </div>
                    </div>
                </div>



                <div id="facilities-list">
                    <?php if (!empty($approvedFacilities)): ?>
                        <?php foreach ($approvedFacilities as $facility): ?>
                            <div class="form-group">
                                <div class="row g-2 align-items-center mb-4 ">
                                    <div class="col-md-2">
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
                                    <div class="col-md-3">
                                        <label for="">Building or Room Number</label>
                                        <?php
                                        // Check if "Bldg." is part of the facility name
                                        $isBuilding = strpos($facility['name'], 'Bldg.') !== false;

                                        // Set the placeholder based on whether the facility name has "Bldg."
                                        $placeholder = $isBuilding ? 'Building or Room Number' : htmlspecialchars($facility['name']);
                                        $readonly = !$isBuilding ? 'readonly' : ''; // Make uneditable if "Bldg." is not in the name
                                        ?>

                                        <input type="text" class="form-control" placeholder="<?= $placeholder ?>"
                                            name="building_or_room[<?= htmlspecialchars($facility['name']) ?>]" <?= $readonly ?>>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="">Date of Use</label>
                                        <input type="date" class="form-control" placeholder="Date of Use" name="date_of_use[<?= htmlspecialchars($facility['name']) ?>]"
                                            value="<?= htmlspecialchars($bookingDate) ?>"> <!-- Pre-fills the date -->
                                    </div>

                                    <div class="col-md-2">
                                        <label for="">time of use</label>
                                        <input type="time" class="form-control" placeholder="Time of Use"
                                            name="time_of_use[<?= htmlspecialchars($facility['name']) ?>]">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="">end time of use</label>
                                        <input type="time" class="form-control" placeholder="End Time of Use"
                                            name="end_time_of_use[<?= htmlspecialchars($facility['name']) ?>]">
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
                    <h3>Request</h3>
                    <div class="row mb-4 text-center">
                        <div class="col-md-4 mb-4">
                            <label class="form-label">Requested by:</label>
                            <input type="text" class="form-control mb-2 text-center fw-bold" name="requested_by"
                                value="<?= htmlspecialchars($userFullName) ?>" readonly>
                            <input type="text" class="form-control mb-2 text-center fw-bold" name="requested_by_designation"
                                value="<?= htmlspecialchars($userDesignation) ?>" readonly>
                        </div>


                        <!-- Submit Button -->
                        <div class="form-group mt-4">
                            <div class="d-flex justify-content-between">
                                <a class="btn btn-secondary" href="studentActivities.php" role="button">Back</a>
                                <button type="submit" class="btn btn-primary mt-4"
                                    id="submitButton">Submit Request</button>
                            </div>
                        </div>

                        <!-- Modal for Pending Requests -->
                        <?php if ($hasPendingRequests && !empty($pendingRequests)): ?>
                            <div class="modal fade" id="pendingRequestsModal" tabindex="-1" aria-labelledby="pendingRequestsModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="pendingRequestsModalLabel">Pending Block Request(s)</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>You have pending block request(s). Please wait until it is approved.</p>
                                            <ul>
                                                <?php foreach ($pendingRequests as $request): ?>
                                                    <li>
                                                        <strong>Facility:</strong> <?= htmlspecialchars($request['facility_name']) ?>
                                                        <br>
                                                        <strong>Date:</strong> <?= htmlspecialchars($request['date']) ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
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
                        <footer>
                            <?php include 'includes/footer.php'; ?>
                        </footer>
            </form>
        </div>

        <!-- Include jQuery before the script -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- Include Popper.js and Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
        <!-- Custom Script to generate facilities list -->
        <script>
            document.getElementById('submitButton').addEventListener('click', function(event) {
                logSubmitRequest(); // Log activity
                document.getElementById('facilityBookingForm').submit(); // Submit the form
            });
        </script>


        <script>
            $(document).ready(function() {
                // Show the appropriate modal based on conditions
                <?php if ($hasPendingRequests): ?>
                    $('#pendingRequestsModal').modal('show');
                <?php elseif ($hasNoRequests): ?>
                    $('#noRequestsModal').modal('show');
                    setTimeout(function() {
                        window.location.href = '/main/IntelliDocM/booking/facilityBooking.php'; // Redirect after 5 seconds
                    }, 5000);
                <?php endif; ?>
            });
        </script>

    </body>

    </html>
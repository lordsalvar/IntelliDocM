<?php

include 'database.php';

$id = $_GET['id']; // Get the proposal ID from the URL

// Fetch the proposal data
$sql = "SELECT * FROM activity_proposals WHERE proposal_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$proposal = $result->fetch_assoc();
$stmt->close();

// Fetch facility bookings for this proposal
$bookings_sql = "SELECT 
    b.booking_date,
    b.start_time,
    b.end_time,
    b.status,
    f.name as facility_name,
    GROUP_CONCAT(r.room_number SEPARATOR ', ') as room_numbers
FROM bookings b
JOIN facilities f ON b.facility_id = f.id
LEFT JOIN booking_rooms br ON b.id = br.booking_id
LEFT JOIN rooms r ON br.room_id = r.id
WHERE b.activity_proposal_id = ?
GROUP BY b.id
ORDER BY b.booking_date, b.start_time";

$bookings_stmt = $conn->prepare($bookings_sql);
$bookings_stmt->bind_param("i", $id);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();
$facility_bookings = $bookings_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Proposal Document</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/print.css" media="print">
    <link rel="stylesheet" href="css/client_view.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
<div class="dashboard">

<div class="container my-5">
<a class="btn btn-secondary mb-3 back-button" href="/main/intellidocm/client_dashboard.php">← Back</a>
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
    <h2 class="text-center mb-4">Proposal Document</h2>

    <?php if ($proposal): ?>
        <div class="mb-4">
            <label for="organizationName" class="form-label">Name of the Organization/ Class/ College:</label>
            <input type="text" class="form-control" id="organizationName" value="<?= htmlspecialchars($proposal['club_name']) ?>" readonly />
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <label for="acronym" class="form-label">Acronym:</label>
                <input type="text" class="form-control" id="acronym" value="<?= htmlspecialchars($proposal['acronym']) ?>" readonly />
            </div>
            <div class="col-md-6">
                <label class="form-label">Organization Category:</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="academic" <?= ($proposal['club_type'] === 'Academic') ? 'checked' : ''; ?> disabled>
                    <label class="form-check-label" for="academic">Academic</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="nonAcademic" <?= ($proposal['club_type'] === 'Non-Academic') ? 'checked' : ''; ?> disabled>
                    <label class="form-check-label" for="nonAcademic">Non-Academic</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="ACCO" <?= ($proposal['club_type'] === 'ACCO') ? 'checked' : ''; ?> disabled>
                    <label class="form-check-label" for="nonAcademic">ACCO</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="CSG" <?= ($proposal['club_type'] === 'CSG') ? 'checked' : ''; ?> disabled>
                    <label class="form-check-label" for="nonAcademic">CSG</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="College-LGU" <?= ($proposal['club_type'] === 'College-LGU') ? 'checked' : ''; ?> disabled>
                    <label class="form-check-label" for="nonAcademic">College-LGU</label>
                </div>
                <!-- Add other checkboxes here based on club types as needed -->
            </div>
        </div>

        <div class="row mb-4">
            <div class="col mb-6">
                <label for="activityTitle" class="form-label">Title of the Activity:</label>
                <input type="text" class="form-control" id="activityTitle" value="<?= htmlspecialchars($proposal['activity_title']) ?>" readonly />
            </div>
            <div class="col mb-6">
                <label class="form-label">Type of Activity:</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="on-campus" <?= ($proposal['activity_type'] === 'On-Campus Activity') ? 'checked' : ''; ?> disabled>
                    <label class="form-check-label" for="on-campus">On-Campus Activity</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="off-campus" <?= ($proposal['activity_type'] === 'Off-Campus Activity') ? 'checked' : ''; ?> disabled>
                    <label class="form-check-label" for="off-campus">Off-Campus Activity</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="online" <?= ($proposal['activity_type'] === 'Online Activity') ? 'checked' : ''; ?> disabled>
                    <label class="form-check-label" for="online">Online Activity</label>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">Objectives:</label>
            <textarea class="form-control" rows="3" readonly><?= htmlspecialchars($proposal['objectives']) ?></textarea>
        </div>

        <div class="mb-4">
            <label class="form-label">Student Development Program Category:</label>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="omp" <?= strpos($proposal['program_category'], 'OMP') !== false ? 'checked' : '' ?> disabled>
                        <label class="form-check-label" for="omp">Organizational Management Development (OMP)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="ksd" <?= strpos($proposal['program_category'], 'KSD') !== false ? 'checked' : '' ?> disabled>
                        <label class="form-check-label" for="ksd">Knowledge & Skills Development (KSD)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="ct" <?= strpos($proposal['program_category'], 'CT') !== false ? 'checked' : '' ?> disabled>
                        <label class="form-check-label" for="ct">Capacity and Teambuilding (CT)</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="srf" <?= strpos($proposal['program_category'], 'SRF') !== false ? 'checked' : '' ?> disabled>
                        <label class="form-check-label" for="srf">Spiritual & Religious Formation (SRF)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="rpInitiative" <?= strpos($proposal['program_category'], 'RPI') !== false ? 'checked' : '' ?> disabled>
                        <label class="form-check-label" for="rpInitiative">Research & Project Initiative (RPI)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="cesa" <?= strpos($proposal['program_category'], 'CESA') !== false ? 'checked' : '' ?> disabled>
                        <label class="form-check-label" for="cesa">Community Engagement & Social Advocacy (CESA)</label>
                    </div>

                </div>
            </div>
        </div>


        <div class="row mb-3">
            <div class="col-md-6">
                <label for="date" class="form-label">Start Date:</label>
                <input type="date" class="form-control" id="date" value="<?= htmlspecialchars($proposal['activity_date']) ?>" readonly />
            </div>
            <div class="col-md-6">
                <label for="endDate" class="form-label">End Date:</label>
                <input type="date" class="form-control" id="endDate" value="<?= htmlspecialchars($proposal['end_activity_date']) ?>" readonly />
            </div>
        </div>


        <div class="mb-4">
                    <h4 class="mb-3">Facility Bookings</h4>
                    <?php if (empty($facility_bookings)): ?>
                        <p class="text-muted">No facilities booked for this activity.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Facility</th>
                                        <th>Room(s)</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($facility_bookings as $booking): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($booking['facility_name']) ?></td>
                                            <td><?= htmlspecialchars($booking['room_numbers'] ?: 'Whole Facility') ?></td>
                                            <td><?= date('F d, Y', strtotime($booking['booking_date'])) ?></td>
                                            <td>
                                                <?= date('h:i A', strtotime($booking['start_time'])) ?> -
                                                <?= date('h:i A', strtotime($booking['end_time'])) ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= strtolower($booking['status']) === 'confirmed' ? 'success' : (strtolower($booking['status']) === 'pending' ? 'warning' : 'danger') ?>">
                                                    <?= htmlspecialchars($booking['status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>


        <div class="row mb-4">
            <div class="col-md-6">
                <label for="targetParticipants" class="form-label">Target Participants:</label>
                <input type="text" class="form-control" id="targetParticipants" value="<?= htmlspecialchars($proposal['target_participants']) ?>" readonly />
            </div>
            <div class="col-md-6">
                <label for="expectedParticipants" class="form-label">Expected Number of Participants:</label>
                <input type="number" class="form-control" id="expectedParticipants" value="<?= htmlspecialchars($proposal['expected_participants']) ?>" readonly />
            </div>
        </div>

            <!-- Signatures Section -->
            <div class="row mb-4 tect-center">
                <div class="col-md-6">
                    <label class="form-label">Applicant</label>
                    <input type="text" class="form-control mb-2" value="<?= htmlspecialchars($proposal['applicant_name']) ?>" readonly />
                    <?php if (!empty($proposal['applicant_signature'])): ?>
                        <div class="qr-code-container text-center">
                            <img src="/main/IntelliDocM/client_qr_codes/<?= basename($proposal['applicant_signature']) ?>" alt="Applicant QR Code" class="qr-code" />
                        </div>
                    <?php else: ?>
                        <p class="text-warning mt-2">Awaiting approval.</p>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Moderator</label>
                    <input type="text" class="form-control mb-2" value="<?= htmlspecialchars($proposal['moderator_name']) ?>" readonly />
                    <?php if (!empty($proposal['moderator_signature'])): ?>
                        <div class="qr-code-container text-center">
                            <img src="/main/IntelliDocM/qr_codes/<?= basename($proposal['moderator_signature']) ?>" alt="Moderator QR Code" class="qr-code" />
                        </div>
                    <?php else: ?>
                        <p class="text-warning mt-2">Awaiting approval.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="text-center">
                <label class="form-label">Noted by:</label>
                <input type="text" class="form-control mb-2" value="<?= htmlspecialchars($proposal['dean_name']) ?>" readonly />
                <?php if (!empty($proposal['dean_signature'])): ?>
                    <div class="qr-code-container text-center">
                        <img src="/main/IntelliDocM/dean_qr_codes/<?= basename($proposal['dean_signature']) ?>" alt="Dean QR Code" class="qr-code" />
                    </div>
                    <p class="text-success mt-2">Date Signed: <?php echo date("Y-m-d"); ?></p>
                <?php else: ?>
                    <p class="text-warning mt-2">Awaiting approval.</p>
                <?php endif; ?>
                <!--<div class="form-row mt-4 d-flex justify-content-center align-items-center" style="height: 100px;">
                    <button onclick="window.print();" class="btn btn-primary mb-3">Print Document</button>
                </div>-->

        <?php else: ?>
            <p>No proposal found with the specified ID.</p>
        <?php endif; ?>
    </div>

    <?php
    // -- Only run this if $proposal was found (and thus $proposalId is set).
    //    But you can wrap it in a check if needed.

    // 2) Run a simple query to check if there's at least one record in the bookings table
    //    for this proposal.
    $proposalId = $proposal['activity_proposal_id'] ?? 0;
    $sql = "SELECT COUNT(*) AS total FROM bookings WHERE activity_proposal_id = '$proposalId'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $bookingCount = (int) $row['total'];

    // 3) If a booking is found for this proposal, show 'View Document', otherwise 'Book Facilities'
    if ($bookingCount > 0) {
        // Already has a booking record
    ?>
        <div class="text-center mt-5">
            <a
                href="view_boking.php?proposal_id=<?= urlencode($proposalId) ?>"
                class="btn btn-success btn-lg">
                View Document
            </a>
        </div>
    <?php
    } else {
        // No booking record yet
    ?>
        <div class="text-center mt-5">
            <?php if ($proposal['status'] === 'Approved'): ?>
                <a href="boking.php?proposal_id=<?= urlencode($proposalId) ?>" class="btn btn-primary btn-lg">
                    Book Facilities
                </a>
            <?php else: ?>
                <button class="btn btn-secondary btn-lg" disabled>
                    Pending
                </button>
            <?php endif; ?>
        </div>
    <?php
    }

    // 4) Close the connection at the very end
    mysqli_close($conn);
    ?>



    <br>


    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.6.0/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
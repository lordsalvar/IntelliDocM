<?php
// 1) Include your database connection
include 'database.php';

// 2) Get the proposal_id from the URL, e.g. view_booking.php?proposal_id=123
$proposalId = isset($_GET['proposal_id']) ? (int) $_GET['proposal_id'] : 0;

// 3) Fetch the booking record that matches this proposal_id
$sql = "SELECT * FROM bookings WHERE proposal_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $proposalId);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    // If no record found in "bookings" for this proposal_id
    $conn->close();
    die("No booking found for proposal_id = " . htmlspecialchars($proposalId));
}

// We'll need the booking ID to look up the booked facilities
$bookingId = $booking['id'];

// 4) Fetch all booked facilities for the found booking ID
//    If you have a `facilities` table with a facility name, you can join it like so:
$sqlFacilities = "
    SELECT bf.*, f.name AS facility_name
    FROM booked_facilities bf
    JOIN facilities f ON bf.facility_id = f.id
    WHERE bf.booking_id = ?
";
$stmt2 = $conn->prepare($sqlFacilities);
$stmt2->bind_param("i", $bookingId);
$stmt2->execute();
$facilitiesResult = $stmt2->get_result();
$facilities = $facilitiesResult->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

// (Optional) Close the connection now that we have all data
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Request for Use of School Facilities - Cor Jesu College</title>
    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet" />
    <!-- Custom CSS (optional) -->
    <link rel="stylesheet" href="css/boking.css" />
</head>

<body>
    <div class="container mt-5">
        <!-- Overlay Box -->
        <div class="overlay-box">
            <p><strong>Index No.:</strong> <u>7.3</u></p>
            <p><strong>Revision No.:</strong> <u>00</u></p>
            <p><strong>Effective Date:</strong> <u>05/16/24</u></p>
            <p><strong>Control No.:</strong> ___________</p>
        </div>

        <div class="header-content">
            <img src="css/img/cjc_logo.png" alt="Logo" class="header-logo" />
            <div class="header-text">
                <h2 class="text-center text-uppercase">Cor Jesu College, Inc.</h2>
                <div class="line yellow-line"></div>
                <div class="line blue-line"></div>
                <p class="text-center">
                    Sacred Heart Avenue, Digos City, Province of Davao del Sur, Philippines
                </p>
                <p class="text-center">
                    Tel. No.: (082) 553-2433 local 101 • Fax No.: (082) 553-2333 • www.cjc.edu.ph
                </p>
            </div>
        </div>

        <div class="text-center mb-4">
            <h4 class="text-uppercase">Request for the Use of School Facilities</h4>
        </div>

        <!-- Display Booking Info -->
        <form>
            <!-- Requesting Party Information -->
            <div class="form-section mb-4">
                <h3>Requesting Party</h3>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nature of Department/Group/Organization</label>
                        <!-- We'll store "club_name" in the "organization_nature" field as an example -->
                        <input
                            type="text"
                            class="form-control"
                            name="organization_nature"
                            value="<?= htmlspecialchars($booking['club_name']) ?>"
                            readonly />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact Number</label>
                        <input
                            type="text"
                            class="form-control"
                            name="contact_number"
                            value="<?= htmlspecialchars($booking['contact_number']) ?>"
                            readonly />
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Purpose of Request</label>
                        <input
                            type="text"
                            class="form-control"
                            name="purpose_request"
                            value="<?= htmlspecialchars($booking['purpose']) ?>"
                            readonly />
                    </div>
                </div>
            </div>

            <!-- Booked Facilities -->
            <div id="facilities-list">
                <h3>Facilities</h3>
                <?php if (!empty($facilities)): ?>
                    <?php foreach ($facilities as $facility): ?>
                        <div class="form-group">
                            <div class="row g-2 align-items-center mb-3">
                                <div class="col-md-2">
                                    <div class="form-check">
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            id="facility_<?= $facility['facility_id'] ?>"
                                            name="facilities[]"
                                            value="<?= htmlspecialchars($facility['facility_id']) ?>"
                                            checked
                                            disabled />
                                        <label
                                            class="form-check-label"
                                            for="facility_<?= $facility['facility_id'] ?>">
                                            <!-- If you joined with the facilities table, you have a name: -->
                                            <?= htmlspecialchars($facility['facility_name']) ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Building/Room</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        name="building_or_room"
                                        value="<?= htmlspecialchars($facility['building_or_room']) ?>"
                                        readonly />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date of Use</label>
                                    <input
                                        type="date"
                                        class="form-control"
                                        name="date_of_use"
                                        value="<?= htmlspecialchars($facility['date_of_use']) ?>"
                                        readonly />
                                </div>
                                <div class="col-md-2 mt-2">
                                    <label class="form-label">Start Time</label>
                                    <input
                                        type="time"
                                        class="form-control"
                                        name="time_of_use"
                                        value="<?= htmlspecialchars($facility['time_of_use']) ?>"
                                        readonly />
                                </div>
                                <div class="col-md-2 mt-2">
                                    <label class="form-label">End Time</label>
                                    <input
                                        type="time"
                                        class="form-control"
                                        name="end_time_of_use"
                                        value="<?= htmlspecialchars($facility['end_time_of_use']) ?>"
                                        readonly />
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No facilities have been booked for this proposal.</p>
                <?php endif; ?>
            </div>

            <!-- Signatures -->
            <div class="form-section mt-5">
                <h3>Signatures</h3>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Requested by:</label>
                        <?php if (!empty($booking['requested_by_signature'])): ?>
                            <p><img
                                    src="path/to/qr_codes/<?= basename($booking['requested_by_signature']) ?>"
                                    alt="Requested By Signature"
                                    style="max-width:120px; height:auto;" /></p>
                        <?php else: ?>
                            <p class="text-warning">Awaiting signature...</p>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">SSC Signature:</label>
                        <?php if (!empty($booking['ssc_signature'])): ?>
                            <p><img
                                    src="path/to/qr_codes/<?= basename($booking['ssc_signature']) ?>"
                                    alt="SSC Signature"
                                    style="max-width:120px; height:auto;" /></p>
                        <?php else: ?>
                            <p class="text-warning">Awaiting signature...</p>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Moderator/Dean Signature:</label>
                        <?php if (!empty($booking['moderator_signature'])): ?>
                            <p><img
                                    src="path/to/qr_codes/<?= basename($booking['moderator_signature']) ?>"
                                    alt="Moderator Signature"
                                    style="max-width:120px; height:auto;" /></p>
                        <?php else: ?>
                            <p class="text-warning">Awaiting signature...</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Security In-charge:</label>
                        <?php if (!empty($booking['security_signature'])): ?>
                            <p><img
                                    src="path/to/qr_codes/<?= basename($booking['security_signature']) ?>"
                                    alt="Security Signature"
                                    style="max-width:120px; height:auto;" /></p>
                        <?php else: ?>
                            <p class="text-warning">Awaiting signature...</p>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Property Custodian:</label>
                        <?php if (!empty($booking['property_custodian_signature'])): ?>
                            <p><img
                                    src="path/to/qr_codes/<?= basename($booking['property_custodian_signature']) ?>"
                                    alt="Property Custodian Signature"
                                    style="max-width:120px; height:auto;" /></p>
                        <?php else: ?>
                            <p class="text-warning">Awaiting signature...</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS (Optional) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script
        src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>

</html>
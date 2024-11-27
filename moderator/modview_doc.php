<?php

include '../database.php';
include '../phpqrcode/qrlib.php';

$id = $_GET['id']; // Get the proposal ID from the URL

// Fetch the proposal data
$sql = "SELECT * FROM activity_proposals WHERE proposal_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$proposal = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_qr'])) {
    // Generate QR Code content
    $qrData = json_encode([
        'proposal_id' => $proposal['proposal_id'],
        'activity_title' => $proposal['activity_title'],
        'moderator_name' => $proposal['moderator_name'],
    ]);

    // Define the directory to save QR codes
    $qrDirectory = "../qr_codes";
    if (!is_dir($qrDirectory)) {
        mkdir($qrDirectory, 0777, true); // Create the directory if it doesn't exist
    }

    // Define the file path for the QR code
    $qrFilePath = $qrDirectory . "/qr_" . $proposal['proposal_id'] . ".png";

    // Generate and save the QR code as a file
    QRcode::png($qrData, $qrFilePath, QR_ECLEVEL_L, 5);

    // Update the moderator_signature column with the file path
    $updateSql = "UPDATE activity_proposals SET moderator_signature = ? WHERE proposal_id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("si", $qrFilePath, $id);

    if ($updateStmt->execute()) {
        echo "<script>alert('Moderator signature added and QR code generated successfully.'); window.location.href='modview_doc.php?id=$id';</script>";
    } else {
        echo "<script>alert('Failed to update moderator signature. Please try again.');</script>";
        error_log('Database update error: ' . $updateStmt->error);
    }
    $updateStmt->close();
}

$conn->close();

?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Proposal Document</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .form-control[readonly],
        .form-check-input[disabled] {
            background-color: #e9ecef;
            color: #6c757d;
        }

        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }
    </style>
</head>

<body>

    <a href="javascript:history.back()" class="btn btn-secondary back-button">
        &larr; Back
    </a>

    <div class="container my-5">
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
                        <input type="text"
                            class="form-control mt-2"
                            name="other_program"
                            placeholder="Others (Please specify)"
                            value="<?= strpos($proposal['program_category'], 'Others') !== false ? htmlspecialchars($proposal['other_program'] ?? '') : '' ?>"
                            <?= strpos($proposal['program_category'], 'Others') === false ? 'disabled' : '' ?>>
                    </div>
                    <!-- Continue with other program categories as necessary -->
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="venue" class="form-label">Venue of the Activity:</label>
                    <input type="text" class="form-control" id="venue" value="<?= htmlspecialchars($proposal['venue']) ?>" readonly />
                </div>
                <div class="col-md-6">
                    <label for="address" class="form-label">Address of the Venue:</label>
                    <input type="text" class="form-control" id="address" value="<?= htmlspecialchars($proposal['address']) ?>" readonly />
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-4">
                    <label for="date" class="form-label">Date of the Activity:</label>
                    <input type="date" class="form-control" id="date" value="<?= htmlspecialchars($proposal['activity_date']) ?>" readonly />
                </div>
                <div class="col-md-4">
                    <label for="startTime" class="form-label">Starting Time:</label>
                    <input type="time" class="form-control" id="startTime" value="<?= htmlspecialchars($proposal['start_time']) ?>" readonly />
                </div>
                <div class="col-md-4">
                    <label for="endTime" class="form-label">Finishing Time:</label>
                    <input type="time" class="form-control" id="endTime" value="<?= htmlspecialchars($proposal['end_time']) ?>" readonly />
                </div>
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
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label">Applicant</label>
                    <input type="text" class="form-control mb-2" value="<?= htmlspecialchars($proposal['applicant_signature']) ?>" readonly />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Moderator</label>
                    <input type="text" class="form-control mb-2" value="<?= htmlspecialchars($proposal['moderator_name']) ?>" readonly />

                    <?php if (!empty($proposal['moderator_signature'])): ?>
                        <div class="qr-code-container text-center">
                            <img src="<?= htmlspecialchars($proposal['moderator_signature']) ?>" alt="Moderator QR Code" class="qr-code" />
                        </div>
                        <p class="text-success mt-2">Date Signed</p>
                    <?php else: ?>
                        <p class="text-warning mt-2">No QR Code generated yet.</p>
                        <form method="POST" class="text-center mt-4">
                            <button type="submit" name="generate_qr" class="btn btn-primary">
                                Sign Document
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Other Faculty/Staff</label>
                    <input type="text" class="form-control mb-2" value="<?= htmlspecialchars($proposal['faculty_signature']) ?>" readonly />
                </div>
            </div>

            <div class="text-center">
                <label class="form-label">Noted by:</label>
                <input type="text" class="form-control mb-2" value="<?= htmlspecialchars($proposal['dean_name']) ?>" readonly />
                <?php if (!empty($proposal['dean_signature'])): ?>
                    <div class="qr-code-container text-center">
                        <img src="data:image/png;base64,<?= base64_encode($proposal['dean_signature']) ?>" alt="Dean QR Code" class="qr-code" />
                    </div>
                    <p class="text-success mt-2">Date Signed</p>
                <?php else: ?>
                    <p class="text-warning mt-2">Awaiting approval.</p>
                <?php endif; ?>
            </div>



        <?php else: ?>
            <p>No proposal found with the specified ID.</p>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.6.0/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
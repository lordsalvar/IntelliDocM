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
    $conn->close();
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>View Proposal Document</title>
        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
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

                <div class="mb-4">
                    <label for="activityTitle" class="form-label">Title of the Activity:</label>
                    <input type="text" class="form-control" id="activityTitle" value="<?= htmlspecialchars($proposal['activity_title']) ?>" readonly />
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
                            <input type="text" class="form-control mt-2" name="other_program" placeholder="Others (Please specify) " disabled>
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
                        <input type="text" class="form-control mb-2" value="<?= htmlspecialchars($proposal['moderator_signature']) ?>" readonly />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Other Faculty/Staff</label>
                        <input type="text" class="form-control mb-2" value="<?= htmlspecialchars($proposal['faculty_signature']) ?>" readonly />
                    </div>
                </div>

                <div class="text-center">
                    <label class="form-label">Noted by:</label>
                    <input type="text" class="form-control mb-2" value="<?= htmlspecialchars($proposal['dean_signature']) ?>" readonly />
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
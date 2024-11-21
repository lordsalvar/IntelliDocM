<?php
session_start();
include 'clientnavbar';
include 'database.php';

// Check if 'id' is provided in the URL


$proposal_id = $_GET['proposal_id']; // Retrieve the proposal ID from the URL
$user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    echo "Error: No proposal ID provided.";
    exit;
}

// Fetch the proposal data if the user is a club member and the proposal is pending
$sql = "
    SELECT p.*, c.club_name, cm.user_id
    FROM activity_proposals p
    JOIN clubs c ON p.club_id = c.club_id
    JOIN club_memberships cm ON cm.club_id = c.club_id
    WHERE p.proposal_id = ? 
    AND cm.user_id = ? 
    AND p.status = 'Pending'";  // Ensure status is correct or rename based on your table

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $proposal_id, $user_id);
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

            <div class="mb-4">
                <label for="activityTitle" class="form-label">Title of the Activity:</label>
                <input type="text" class="form-control" id="activityTitle" value="<?= htmlspecialchars($proposal['activity_title']) ?>" readonly />
            </div>

            <div class="mb-4">
                <label class="form-label">Objectives:</label>
                <textarea class="form-control" rows="3" readonly><?= htmlspecialchars($proposal['objectives']) ?></textarea>
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
        <?php else: ?>
            <p>No proposal found with the specified ID, or you do not have permission to view this proposal.</p>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.6.0/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
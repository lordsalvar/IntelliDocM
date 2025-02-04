<?php
session_start();
require_once 'database.php';
include 'system_log/activity_log.php';

// Start session and validate the user's login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];
$userActivity = 'User visited the dashboard';
logActivity($username, $userActivity);

// Fetch user's club membership
$user_id = $_SESSION['user_id'];
$club_query = "
    SELECT c.club_name 
    FROM club_memberships cm
    JOIN clubs c ON cm.club_id = c.club_id
    WHERE cm.user_id = ?
";
$stmt = $conn->prepare($club_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$club_result = $stmt->get_result();
$club_name = $club_result->fetch_assoc()['club_name'] ?? null;

// If no club is found, display a message
if (!$club_name) {
    echo "<p>You are not currently associated with any club.</p>";
    exit();
}

// Fetch proposals related to the user's club
$sql_proposals = "SELECT * FROM activity_proposals WHERE club_name = ?";
$stmt = $conn->prepare($sql_proposals);
$stmt->bind_param("s", $club_name);
$stmt->execute();
$proposals_result = $stmt->get_result();

//$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client</title>
    <link href="css/client.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script>
        // Log activity when the user clicks "View Document"
        function logDocumentViewActivity(documentTitle, documentId) {
            const username = '<?php echo $_SESSION['username']; ?>'; // Get the username from PHP session

            const userActivity = `User viewed document: ${documentTitle} (ID: ${documentId})`;

            // Send AJAX request to log the activity
            logActivity(userActivity, documentTitle, documentId, username);
        }

        // Log activity function to send the AJAX request
        function logActivity(userActivity, documentTitle, documentId, username) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "system_log/log_activity.php", true); // Ensure this points to your PHP logging handler
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    // Handle response if needed
                    console.log('Activity logged successfully.');
                }
            };
            xhr.send("activity=" + encodeURIComponent(userActivity) + "&document_title=" + encodeURIComponent(documentTitle) + "&document_id=" + encodeURIComponent(documentId) + "&username=" + encodeURIComponent(username));
        }
    </script>

</head>

<body>
    <header>
        <?php include 'includes/clientnavbar.php'; ?>
    </header>

    <div class="container mt-5">
        <h2 class="text-center text-white mb-4">Submitted Proposals for <?= htmlspecialchars($club_name) ?></h2>

        <!-- Proposals Table -->
        <?php if ($proposals_result && $proposals_result->num_rows > 0): ?>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th class="text-center">Title</th>
                        <th class="text-center">Date of activity</th>
                        <th class="text-center">Start Time</th>
                        <th class="text-center">Finish Time</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $proposals_result->fetch_assoc()): ?>
                        <tr>
                            <td class="text-center"><?= htmlspecialchars($row['activity_title'] ?? '') ?></td>
                            <td class="text-center"><?= htmlspecialchars($row['activity_date'] ?? '') ?></td>
                            <td class="text-center"><?= htmlspecialchars($row['start_time'] ?? '') ?></td>
                            <td class="text-center"><?= htmlspecialchars($row['end_time'] ?? '') ?></td>
                            <td class="text-center"><?= htmlspecialchars($row['status'] ?? 'Pending') ?></td>
                            <td class="text-center">
                                <a href="client_view.php?id=<?= $row['proposal_id'] ?>" class="btn btn-primary btn-sm" onclick="logDocumentViewActivity('<?= htmlspecialchars($row['activity_title'] ?? '') ?>', <?= $row['proposal_id'] ?>)">
                                    View Document
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <h5 class="text-center text-white">No proposals found for your club.</h5>
        <?php endif; ?>
    </div>

    <footer>
        <?php include 'includes/footer.php'; ?>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
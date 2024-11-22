<?php
include 'database.php';

// Redirect to login if the user is not logged in
session_start();
if ($_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit();
}

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
$sql = "SELECT * FROM activity_proposals WHERE club_name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $club_name);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Proposals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>

<body>
    <?php include 'includes/clientnavbar.php' ?>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Submitted Proposals for <?= htmlspecialchars($club_name) ?></h2>

        <?php if ($result && $result->num_rows > 0): ?>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th class="text-center">Title</th>
                        <th class="text-center">Date</th>
                        <th class="text-center">Start Time</th>
                        <th class="text-center">Finish Time</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="text-center"><?= htmlspecialchars($row['activity_title'] ?? '') ?></td>
                            <td class="text-center"><?= htmlspecialchars($row['activity_date'] ?? '') ?></td>
                            <td class="text-center"><?= htmlspecialchars($row['start_time'] ?? '') ?></td>
                            <td class="text-center"><?= htmlspecialchars($row['end_time'] ?? '') ?></td>
                            <td class="text-center"><?= htmlspecialchars($row['status'] ?? 'Pending') ?></td>
                            <td class="text-center">
                                <a href="client_view.php?id=<?= $row['proposal_id'] ?>" class="btn btn-primary btn-sm">View Document</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No proposals found for your club.</p>
        <?php endif; ?>

        <?php $conn->close(); ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
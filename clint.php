<?php
require_once 'database.php';
require_once 'includes/notifications.php';

// Start session and validate the user's login
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
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

// Fetch unread notifications
$notifications = getUnreadNotifications($user_id, $conn);

// If no club is found, display a message
if (!$club_name) {
    echo "<div class='container mt-5'><div class='alert alert-warning'>You are not currently associated with any club.</div></div>";
    exit();
}

// Fetch proposals related to the user's club
$sql_proposals = "SELECT * FROM activity_proposals WHERE club_name = ?";
$stmt = $conn->prepare($sql_proposals);
$stmt->bind_param("s", $club_name);
$stmt->execute();
$proposals_result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Meta Tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Proposals</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f8f9fa;
        }

        .table thead th {
            background-color: #dc3545;
            color: #fff;
        }

        .btn-primary {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-primary:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
        }
    </style>
</head>

<body>
    <!-- Include Navbar -->
    <?php include 'includes/clientnavbar.php'; ?>

    <div class="container mt-5">
        <h2 class="text-center mb-4">Submitted Proposals for <span class="text-danger"><?= htmlspecialchars($club_name) ?></span></h2>

        <!-- Proposals Table -->
        <?php if ($proposals_result && $proposals_result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead class="text-center">
                        <tr>
                            <th>Title</th>
                            <th>Date of Activity</th>
                            <th>Start Time</th>
                            <th>Finish Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $proposals_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['activity_title'] ?? '') ?></td>
                                <td><?= htmlspecialchars(date('F d, Y', strtotime($row['activity_date'] ?? ''))) ?></td>
                                <td><?= htmlspecialchars(date('h:i A', strtotime($row['start_time'] ?? ''))) ?></td>
                                <td><?= htmlspecialchars(date('h:i A', strtotime($row['end_time'] ?? ''))) ?></td>
                                <td class="text-center">
                                    <?php
                                    $status = htmlspecialchars($row['status'] ?? 'Pending');
                                    $badgeClass = 'secondary';
                                    if ($status == 'Approved') $badgeClass = 'success';
                                    elseif ($status == 'Rejected') $badgeClass = 'danger';
                                    elseif ($status == 'Pending') $badgeClass = 'warning';
                                    ?>
                                    <span class="badge bg-<?= $badgeClass ?>"><?= $status ?></span>
                                </td>
                                <td class="text-center">
                                    <a href="client_view.php?id=<?= $row['proposal_id'] ?>" class="btn btn-sm btn-outline-primary">View Document</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">No proposals found for your club.</div>
        <?php endif; ?>
    </div>

    <!-- Include Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Optional JavaScript for additional functionality -->
</body>

</html>
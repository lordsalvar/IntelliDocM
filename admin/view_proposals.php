<?php
include '../database.php';

// Redirect to login if the admin is not logged in
session_start();
if ($_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Initialize variables
$statusFilter = '';

// Check if a status filter has been submitted
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $statusFilter = $_GET['status'];
    // Prepare a parameterized statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM activity_proposals WHERE status = ?");
    $stmt->bind_param("s", $statusFilter);
} else {
    // No status filter; retrieve all proposals
    $stmt = $conn->prepare("SELECT * FROM activity_proposals");
}

// Execute the query
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Your existing head content -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Proposals</title>
    <link rel="stylesheet" href="../css/view_proposals.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>

<body>
    <header>
    
        <?php include '../includes/navbar.php' ?>
    </header>

    <div class="container mt-5">
        <h2 class="text-center mb-4 text-white">Submitted Proposals</h2>
        <!-- Filter Form -->
        <form method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-4 offset-md-4">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Filter by Status --</option>
                        <option value="Pending" <?= $statusFilter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Received " <?= $statusFilter == 'Received' ? 'selected' : '' ?>>Received</option>
                        <option value="Approved" <?= $statusFilter == 'Approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="Rejected" <?= $statusFilter == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
            </div>
        </form>

        <?php if ($result && $result->num_rows > 0): ?>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th class="text-center">Organization</th>
                        <th class="text-center">Title</th>
                        <th class="text-center">Date of Activity</th>
                        <th class="text-center">Start Time</th>
                        <th class="text-center">Finish Time</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="text-center"><?= htmlspecialchars($row['club_name'] ?? '') ?></td>
                            <td class="text-center"><?= htmlspecialchars($row['activity_title'] ?? '') ?></td>
                            <td class="text-center"><?= htmlspecialchars($row['activity_date'] ?? '') ?></td>
                            <td class="text-center"><?= htmlspecialchars($row['start_time'] ?? '') ?></td>
                            <td class="text-center"><?= htmlspecialchars($row['end_time'] ?? '') ?></td>
                            <td class="text-center"><?= htmlspecialchars($row['status'] ?? 'Pending') ?></td>
                            <td class="text-center">
                                <a href="../view_document.php?id=<?= $row['proposal_id'] ?>" class="btn btn-primary btn-sm">View Document</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No proposals found<?= $statusFilter ? ' for the selected status.' : '.' ?></p>
        <?php endif; ?>

        <?php
        // Close the statement and connection
        $stmt->close();
        $conn->close();
        ?>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Proposal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="rejectForm" method="POST" action="../approvals/reject.php">
                    <div class="modal-body">
                        <p>Are you sure you want to reject this proposal?</p>
                        <input type="hidden" name="proposal_id" id="proposalId">
                        <div class="mb-3">
                            <label for="rejectionReason" class="form-label">Reason for Rejection</label>
                            <textarea class="form-control" id="rejectionReason" name="rejection_reason" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <footer>
    <?php include '../includes/footer.php' ?>
        </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    
</body>

</html>
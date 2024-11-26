<?php
include '../database.php';

// Redirect to login if the admin is not logged in
session_start();
if ($_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Query the activity_proposals table
$sql = "SELECT * FROM activity_proposals";
$result = $conn->query($sql);
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
    <?php include '../includes/navbar.php' ?>
    <hr>
    <hr>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Submitted Proposals</h2>

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
                                <a href="../approvals/approve.php?id=<?= $row['proposal_id'] ?>" class="btn btn-success btn-sm">Approve</a>
                                <button
                                    class="btn btn-danger btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#rejectModal"
                                    data-id="<?= $row['proposal_id'] ?>">Reject</button>
                                <a href="../view_document.php?id=<?= $row['proposal_id'] ?>" class="btn btn-primary btn-sm">View Document</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No proposals found.</p>
        <?php endif; ?>

        <?php $conn->close(); ?>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Reject Proposal</h5>
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

    <?php include '../includes/footer.php' ?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        const rejectModal = document.getElementById('rejectModal');
        rejectModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const proposalId = button.getAttribute('data-id');
            const modalInput = rejectModal.querySelector('#proposalId');
            modalInput.value = proposalId;
        });
    </script>
</body>

</html>
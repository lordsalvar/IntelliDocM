<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <title>Admin - Block Requests</title>
</head>

<body>
    <header>
    <?php include '../includes/navbar.php' ?>
    </header>

    <div class="container mt-5">
        <h1 class="text-center">Admin Panel - Manage Block Requests</h1>
        <div class="table-responsive mt-4">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Club</th>
                        <th>Facility</th>
                        <th>Date</th>
                        <th>Requested By</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    include '../database.php'; // Include your database connection file
                    $conn = getDbConnection();

                    $sql = "SELECT 
                                br.id AS request_id, 
                                c.club_name AS club_name, 
                                f.name AS facility_name, 
                                br.date, 
                                u.full_name AS requested_by, 
                                br.status
                            FROM block_requests br
                            JOIN clubs c ON br.club_id = c.club_id
                            JOIN facilities f ON br.facility_id = f.id
                            JOIN users u ON br.requested_by = u.id
                            ORDER BY br.status DESC, br.date ASC";

                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        $count = 1;
                        while ($row = $result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . $count++ . '</td>';
                            echo '<td>' . htmlspecialchars($row['club_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['facility_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['date']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['requested_by']) . '</td>';
                            echo '<td>' . ucfirst(htmlspecialchars($row['status'])) . '</td>';
                            echo '<td>';
                            if ($row['status'] === 'pending') {
                                echo '<a href="process_approval.php?id=' . $row['request_id'] . '&action=approve" class="btn btn-success btn-sm">Approve</a> ';
                                echo '<a href="process_approval.php?id=' . $row['request_id'] . '&action=reject" class="btn btn-danger btn-sm">Reject</a>';
                            } else {
                                echo '<span class="text-muted">No Actions</span>';
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="7" class="text-center">No block requests found.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <footer>
    <?php include '../includes/footer.php' ?>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
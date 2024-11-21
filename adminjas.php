<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Fetch data for activities and other details
//$activities = $pdo->query("SELECT * FROM activities")->fetchAll(PDO::FETCH_ASSOC);

// Additional code to handle actions, e.g., approving/disapproving activities, adding users
/*if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve'])) {
        $activityId = $_POST['activity_id'];
        $pdo->prepare("UPDATE activities SET status = 'Approved' WHERE id = ?")->execute([$activityId]);
    } elseif (isset($_POST['disapprove'])) {
        $activityId = $_POST['activity_id'];
        $reason = $_POST['disapproval_reason'];
        $pdo->prepare("UPDATE activities SET status = 'Disapproved', reason = ? WHERE id = ?")->execute([$reason, $activityId]);
    } elseif (isset($_POST['add_user'])) {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $clubName = $_POST['club_name'];
        $pdo->prepare("INSERT INTO authorized_users (username, password, club_name) VALUES (?, ?, ?)")->execute([$username, $password, $clubName]);
    }
}
*/
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .sidebar {
            height: 100vh;
            position: fixed;
            width: 200px;
            background-color: #f8f9fa;
            padding-top: 20px;
        }

        .sidebar a {
            display: block;
            padding: 10px;
            color: #333;
            text-decoration: none;
        }

        .sidebar a:hover {
            background-color: #007bff;
            color: white;
        }

        .content {
            margin-left: 220px;
            /* Same width as sidebar + some spacing */
            padding: 20px;
        }
    </style>
</head>

<body>

    <!-- Main Content -->
    <div class="content">
        <div class="container my-5">
            <h2>Admin Dashboard</h2>

            <!-- Status Logic Section -->
            <section class="my-4">
                <h3>Activity Proposals</h3>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Activity</th>
                            <th>Club</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $activity): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($activity['title']); ?></td>
                                <td><?php echo htmlspecialchars($activity['club_name']); ?></td>
                                <td><?php echo htmlspecialchars($activity['status']); ?></td>
                                <td>
                                    <?php if ($activity['status'] === 'Received'): ?>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="activity_id" value="<?php echo $activity['id']; ?>">
                                            <button type="submit" name="approve" class="btn btn-success">Approve</button>
                                        </form>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="activity_id" value="<?php echo $activity['id']; ?>">
                                            <input type="text" name="disapproval_reason" placeholder="Reason" required>
                                            <button type="submit" name="disapprove" class="btn btn-danger">Disapprove</button>
                                        </form>
                                    <?php elseif ($activity['status'] === 'Approved'): ?>
                                        <button class="btn btn-secondary">Generate QR</button>
                                    <?php elseif ($activity['status'] === 'Disapproved'): ?>
                                        <small>Reason: <?php echo htmlspecialchars($activity['reason']); ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
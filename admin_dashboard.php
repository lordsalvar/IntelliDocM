<?php
session_start();
require_once 'database.php';

// Validate admin login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Fetch admin info and update session
$admin_id = $_SESSION['user_id'];
$admin_query = "SELECT full_name FROM users WHERE id = ?";
$stmt = $conn->prepare($admin_query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_data = $stmt->get_result()->fetch_assoc();

// Update the session with the full name if it's not set
if (!isset($_SESSION['full_name']) && $admin_data) {
    $_SESSION['full_name'] = $admin_data['full_name'];
}

// Fetch recent proposals
$proposals_query = "SELECT * FROM activity_proposals ORDER BY submitted_date DESC LIMIT 5";
$proposals_result = $conn->query($proposals_query);

// Fetch system statistics
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetch_row()[0],
    'total_proposals' => $conn->query("SELECT COUNT(*) FROM activity_proposals")->fetch_row()[0],
    'pending_proposals' => $conn->query("SELECT COUNT(*) FROM activity_proposals WHERE status = 'pending'")->fetch_row()[0],
    'total_clubs' => $conn->query("SELECT COUNT(*) FROM clubs")->fetch_row()[0]
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - IntelliDoc</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/admin.css">
</head>

<body>
    <div class="dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="content">
            <div class="welcome-card">
                <h2>Welcome, <?php echo htmlspecialchars($admin_data['full_name']); ?></h2>
                <p>Here's what's happening in your system</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card users">
                    <i class="fas fa-users"></i>
                    <div class="stat-info">
                        <h3>Total Users</h3>
                        <span class="stat-number"><?php echo $stats['total_users']; ?></span>
                    </div>
                </div>
                <div class="stat-card proposals">
                    <i class="fas fa-file-alt"></i>
                    <div class="stat-info">
                        <h3>Total Proposals</h3>
                        <span class="stat-number"><?php echo $stats['total_proposals']; ?></span>
                    </div>
                </div>
                <div class="stat-card pending">
                    <i class="fas fa-clock"></i>
                    <div class="stat-info">
                        <h3>Pending Approvals</h3>
                        <span class="stat-number"><?php echo $stats['pending_proposals']; ?></span>
                    </div>
                </div>
                <div class="stat-card clubs">
                    <i class="fas fa-building"></i>
                    <div class="stat-info">
                        <h3>Active Clubs</h3>
                        <span class="stat-number"><?php echo $stats['total_clubs']; ?></span>
                    </div>
                </div>
            </div>

            <div class="recent-proposals">
                <div class="section-header">
                    <h3><i class="fas fa-clipboard-list"></i> Recent Proposals</h3>
                    <a href="admin/view_proposals.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="proposals-list">
                    <?php if ($proposals_result->num_rows > 0): ?>
                        <?php while ($proposal = $proposals_result->fetch_assoc()): ?>
                            <div class="proposal-card">
                                <div class="proposal-info">
                                    <h4><?= htmlspecialchars($proposal['activity_title']) ?></h4>
                                    <p class="club-name"><i class="fas fa-users"></i> <?= htmlspecialchars($proposal['club_name']) ?></p>
                                    <span class="date"><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($proposal['activity_date'])) ?></span>
                                </div>
                                <div class="proposal-status">
                                    <span class="status-badge <?= strtolower($proposal['status']) ?>">
                                        <?= ucfirst(htmlspecialchars($proposal['status'])) ?>
                                    </span>
                                    <a href="admin/view_proposal.php?id=<?= $proposal['proposal_id'] ?>" class="view-btn">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-folder-open"></i>
                            <p>No proposals found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
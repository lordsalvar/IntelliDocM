<?php
session_start();
require_once 'database.php';

// Validate user login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit();
}

// Fetch notifications for the user - simplified query for your table structure
$user_id = $_SESSION['user_id'];
$notifications_query = "
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC
";
$stmt = $conn->prepare($notifications_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result();

// Mark all as read functionality
if (isset($_POST['mark_all_read'])) {
    $update_query = "UPDATE notifications SET status = 'read' WHERE user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    header('Location: notifications.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - IntelliDoc</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/notifications.css">
</head>

<body>
    <div class="dashboard">
        <?php include 'includes/sidebar.php'; ?>
        <div class="content">
            <div class="notifications-container">
                <div class="notifications-header">
                    <div class="header-left">
                        <h2><i class="fas fa-bell"></i> Notifications</h2>
                        <span class="notification-count">
                            <?php
                            $unread = mysqli_query($conn, "SELECT COUNT(*) FROM notifications WHERE user_id = $user_id AND status = 'unread'")->fetch_row()[0];
                            echo $unread ? "($unread)" : "";
                            ?>
                        </span>
                    </div>
                    <?php if ($unread > 0): ?>
                        <form method="POST" class="mark-all-read">
                            <button type="submit" name="mark_all_read" class="mark-read-btn">
                                <i class="fas fa-check-double"></i> Mark all as read
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="notifications-list">
                    <?php if ($notifications->num_rows > 0): ?>
                        <?php while ($notification = $notifications->fetch_assoc()): ?>
                            <div class="notification-item <?= $notification['status'] == 'unread' ? 'unread' : 'read' ?>">
                                <div class="notification-icon">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-header">
                                        <span class="notification-time" title="<?= date('F j, Y g:i A', strtotime($notification['created_at'])) ?>">
                                            <?= date('M d, g:i A', strtotime($notification['created_at'])) ?>
                                        </span>
                                    </div>
                                    <p><?= htmlspecialchars($notification['message']) ?></p>
                                    <?php if ($notification['status'] == 'unread'): ?>
                                        <div class="notification-actions">
                                            <a href="ajax/mark_read.php?id=<?= $notification['id'] ?>"
                                                class="mark-read">
                                                <i class="fas fa-check"></i> Mark as read
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <p>No notifications found</p>
                            <span>When you receive notifications, they will appear here</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto refresh notifications every 5 minutes
        setInterval(() => {
            window.location.reload();
        }, 300000);
    </script>
</body>

</html>
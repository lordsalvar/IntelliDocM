<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require_once 'database.php';
require_once 'includes/notifications.php';


// Fetch unread notifications for the logged-in user
$user_id = $_SESSION['user_id'];
$notifications = getUnreadNotifications($user_id, $conn);
?>

<!-- Notification Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #e31b23;">
                <h5 class="modal-title text-white" id="notificationModalLabel">Notifications</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (!empty($notifications)): ?>
                    <ul class="list-group">
                        <?php foreach ($notifications as $notification): ?>
                            <li class="list-group-item">
                                <?= htmlspecialchars($notification['message']) ?><br>
                                <small class="text-muted"><?= htmlspecialchars($notification['timestamp']) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No new notifications.</p>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
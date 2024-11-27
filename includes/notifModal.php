<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/notifications.php';

$user_id = $_SESSION['user_id'];

// Pagination variables
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch notifications for the current page
$notifications = getUnreadNotifications($user_id, $conn, $limit, $offset);

// Fetch total notifications count for pagination
$sql = "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$totalNotifications = $result->fetch_assoc()['total'];
$totalPages = ceil($totalNotifications / $limit);
?>
<style>
    .pagination .page-item.active .page-link {
        background-color: #e31b23;
        /* Match IntelliDoc theme */
        border-color: #e31b23;
        /* IntelliDoc red border */
        color: #fff;
        /* White text for contrast */
    }

    .pagination .page-item .page-link {
        color: #007bff;
        /* Default Bootstrap blue for non-active links */
    }

    .pagination .page-item .page-link:hover {
        background-color: #f5f5f5;
        /* Light gray background on hover */
    }
</style>


<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #e31b23;">
                <h5 class="modal-title" id="notificationModalLabel">Notifications</h5>
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
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
            <?php if ($totalPages > 1): ?>
                <div class="modal-footer">
                    <!-- Pagination Controls -->
                    <nav>
                        <ul class="pagination">
                            <!-- Previous Button -->
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#notificationModal">Previous</a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">Previous</span>
                                </li>
                            <?php endif; ?>

                            <!-- Page Numbers -->
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#notificationModal"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <!-- Next Button -->
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#notificationModal">Next</a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">Next</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>

                <?php endif; ?>


                </div>

        </div>

    </div>

</div>
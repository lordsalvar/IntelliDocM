<?php
session_start();
require_once 'database.php';
include 'system_log/activity_log.php';

function getTimeBasedGreeting()
{
    $hour = date('H');
    if ($hour >= 5 && $hour < 12) {
        return ['greeting' => 'Good Morning', 'icon' => 'sun'];
    } elseif ($hour >= 12 && $hour < 17) {
        return ['greeting' => 'Good Afternoon', 'icon' => 'sun'];
    } elseif ($hour >= 17 && $hour < 21) {
        return ['greeting' => 'Good Evening', 'icon' => 'moon'];
    } else {
        return ['greeting' => 'Good Night', 'icon' => 'moon'];
    }
}

// Validate user login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit();
}

// Fetch user's full name
$user_id = $_SESSION['user_id'];
$user_query = "SELECT full_name FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
$_SESSION['full_name'] = $user_data['full_name'];

// Fetch user's club membership
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

// Fetch proposals if club exists
if ($club_name) {
    $sql_proposals = "SELECT * FROM activity_proposals WHERE club_name = ? ORDER BY activity_date DESC LIMIT 5";
    $stmt = $conn->prepare($sql_proposals);
    $stmt->bind_param("s", $club_name);
    $stmt->execute();
    $proposals_result = $stmt->get_result();
}

// Replace the existing calendar events query with this:
$mini_calendar_events_query = $conn->prepare("
    SELECT 
        activity_title as title,
        activity_date as start,
        end_activity_date as end,
        status,
        'activity' as type,
        DATEDIFF(end_activity_date, activity_date) as duration
    FROM activity_proposals 
    WHERE activity_date >= CURRENT_DATE 
    AND activity_date <= DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)
    AND club_name = ?
    AND status = 'confirmed'
    UNION ALL
    SELECT 
        CONCAT(f.name, ' - ', GROUP_CONCAT(r.room_number)) as title,
        booking_date as start,
        booking_date as end,
        status,
        'booking' as type,
        0 as duration
    FROM bookings b
    JOIN facilities f ON b.facility_id = f.id
    LEFT JOIN booking_rooms br ON b.id = br.booking_id
    LEFT JOIN rooms r ON br.room_id = r.id
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN club_memberships cm ON u.id = cm.user_id
    LEFT JOIN clubs c ON cm.club_id = c.club_id
    WHERE booking_date >= CURRENT_DATE 
    AND booking_date <= DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)
    AND c.club_name = ?
    AND b.status = 'Confirmed'
    GROUP BY b.id
    ORDER BY start ASC
    LIMIT 10
");

// Execute the prepared statement
if ($club_name) {
    $mini_calendar_events_query->bind_param("ss", $club_name, $club_name);
    $mini_calendar_events_query->execute();
    $mini_calendar_events = $mini_calendar_events_query->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $mini_calendar_events = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/activity_logger.js" defer></script>
    <meta name="username" content="<?php echo htmlspecialchars($_SESSION['full_name']); ?>">
</head>

<body>
    <div class="dashboard">
        <?php include 'includes/sidebar.php'; ?>
        <div class="content">
            <div class="welcome-card">
                <?php
                $timeInfo = getTimeBasedGreeting();
                $currentTime = date('F j, Y, g:i a');
                ?>
                <div class="welcome-header">
                    <div class="welcome-text">
                        <h2><?= $timeInfo['greeting'] ?>, <?= htmlspecialchars($_SESSION['full_name']); ?>
                            <i class="fas fa-<?= $timeInfo['icon'] ?> <?= $timeInfo['icon'] ?>-icon"></i>
                        </h2>
                        <span class="current-time"><?= $currentTime ?></span>
                    </div>
                </div>
                <p class="motto">Ametur Cor Jesu, Ametur Cor Mariae!</p>
            </div>

            <div class="calendar-widget">
                <div class="section-header">
                    <div class="header-left">
                        <h3><i class="fas fa-calendar-alt"></i> Upcoming Activities</h3>
                        <p class="subtitle">Next 7 days schedule</p>
                    </div>
                    <a href="calendar.php" class="view-all">
                        Full Calendar <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="calendar-events">
                    <?php if (empty($mini_calendar_events)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-day"></i>
                            <p>No upcoming activities for the next 7 days</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($mini_calendar_events as $event): ?>
                            <div class="event-item <?= $event['type'] ?> <?= strtolower($event['status']) ?>">
                                <div class="event-date">
                                    <i class="fas fa-<?= $event['type'] === 'activity' ? 'calendar-check' : 'door-open' ?>"></i>
                                    <?php if ($event['type'] === 'activity' && $event['duration'] > 0): ?>
                                        <span class="date-range">
                                            <?= date('M d', strtotime($event['start'])) ?> - <?= date('M d', strtotime($event['end'])) ?>
                                        </span>
                                        <span class="duration"><?= $event['duration'] + 1 ?> days</span>
                                    <?php else: ?>
                                        <span class="date"><?= date('M d', strtotime($event['start'])) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="event-details">
                                    <div class="event-info">
                                        <h4><?= htmlspecialchars($event['title']) ?></h4>
                                        <?php if ($event['type'] === 'activity' && $event['duration'] > 0): ?>
                                            <span class="event-duration">
                                                <i class="fas fa-clock"></i>
                                                <?= $event['duration'] + 1 ?> day<?= $event['duration'] > 0 ? 's' : '' ?> activity
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="event-status">
                                        <i class="fas fa-circle"></i>
                                        <?= ucfirst($event['status']) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <h3>Quick Actions</h3>
                <div class="quick-actions" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                    <button style="padding: 1rem; border: none;">
                        <i class="fas fa-upload"></i> Upload Document
                    </button>
                    <button style="padding: 1rem; border: none;">
                        <i class="fas fa-folder"></i> View All Documents
                    </button>
                </div>
            </div>
            <div class="proposals-card">
                <div class="proposals-header">
                    <h3 class="proposals-title">
                        <i class="fas fa-clipboard-list"></i>
                        Recent Activity Proposals for <?= htmlspecialchars($club_name ?? 'No Club') ?>
                    </h3>
                </div>

                <?php if (isset($proposals_result) && $proposals_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="proposals-table">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-file-alt"></i> Title</th>
                                    <th><i class="fas fa-calendar"></i> Date</th>
                                    <th><i class="fas fa-clock"></i> Time</th>
                                    <th><i class="fas fa-info-circle"></i> Status</th>
                                    <th><i class="fas fa-cog"></i> Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $proposals_result->fetch_assoc()): ?>
                                    <tr>
                                        <td data-label="Title"><?= htmlspecialchars($row['activity_title']) ?></td>
                                        <td data-label="Date"><?= date('M d, Y', strtotime($row['activity_date'])) ?></td>
                                        <td data-label="Time"><?= date('h:i A', strtotime($row['start_time'])) ?></td>
                                        <td data-label="Status">
                                            <span class="status-badge <?= strtolower($row['status']) ?>">
                                                <i class="fas fa-circle"></i>
                                                <?= htmlspecialchars($row['status'] ?? 'Pending') ?>
                                            </span>
                                        </td>
                                        <td data-label="Actions">
                                            <a href="client_view.php?id=<?= $row['proposal_id'] ?>"
                                                class="view-btn"
                                                onclick="logDocumentViewActivity('<?= htmlspecialchars($row['activity_title']) ?>', <?= $row['proposal_id'] ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <p>No activity proposals found for your club.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <style>
        .content {
            padding: 1rem;
            max-width: 100%;
            /* Changed from 1200px */
            margin: 0 1rem;
            /* Changed from margin: 0 auto */
        }

        .welcome-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            /* Reduced from 1.5rem */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #8B0000;
        }

        .card,
        .proposals-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            /* Reduced from 1.5rem */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .welcome-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .welcome-text h2 {
            color: #2c3e50;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.8rem;
        }

        .current-time {
            color: #666;
            font-size: 1rem;
            display: block;
            margin-top: 0.5rem;
        }

        .motto {
            color: #8B0000;
            font-size: 1.1rem;
            font-style: italic;
            margin: 0.5rem 0 0 0;
            text-align: right;
        }

        .sun-icon {
            color: #f39c12;
            animation: pulse 2s infinite;
        }

        .moon-icon {
            color: #34495e;
            animation: glow 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        @keyframes glow {
            0% {
                filter: brightness(1);
            }

            50% {
                filter: brightness(1.3);
            }

            100% {
                filter: brightness(1);
            }
        }

        @media (max-width: 768px) {
            .content {
                margin: 0 0.5rem;
                /* Even smaller margin on mobile */
                padding: 0.5rem;
            }

            .welcome-card,
            .card,
            .proposals-card {
                padding: 1rem;
                margin-bottom: 1rem;
            }
        }

        .calendar-widget {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 1rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .header-left h3 {
            margin: 0;
            font-size: 1.2rem;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .header-left .subtitle {
            color: #666;
            font-size: 0.9rem;
            margin: 0.25rem 0 0 0;
        }

        .view-all {
            color: #1976d2;
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .calendar-events {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            max-height: 400px;
            overflow-y: auto;
        }

        .event-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 8px;
            background: #f8f9fa;
            transition: all 0.2s ease;
        }

        .event-item:hover {
            transform: translateX(5px);
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .event-date {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 80px;
            padding: 0.5rem;
            border-radius: 6px;
        }

        .event-date i {
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }

        .date-range {
            font-size: 0.8rem;
            text-align: center;
            line-height: 1.2;
        }

        .duration {
            font-size: 0.75rem;
            color: #666;
            margin-top: 0.25rem;
            display: block;
        }

        .event-details {
            flex: 1;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .event-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .event-info h4 {
            margin: 0;
            font-size: 0.95rem;
            color: #2c3e50;
        }

        .event-duration {
            font-size: 0.8rem;
            color: #666;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .event-duration i {
            font-size: 0.75rem;
            color: #1976d2;
        }

        .event-status {
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .event-status i {
            font-size: 0.6rem;
        }

        .event-item.activity .event-date {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .event-item.booking .event-date {
            background: #fff3e0;
            color: #f57c00;
        }

        .event-item.activity.confirmed .event-status,
        .event-item.booking.confirmed .event-status {
            color: #2e7d32;
        }

        .calendar-events::-webkit-scrollbar {
            width: 6px;
        }

        .calendar-events::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .calendar-events::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 3px;
        }

        .calendar-events::-webkit-scrollbar-thumb:hover {
            background: #999;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .empty-state i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #ccc;
        }
    </style>

    <script>
        function updateTime() {
            const timeElement = document.querySelector('.current-time');
            if (timeElement) {
                const now = new Date();
                const options = {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: 'numeric',
                    second: 'numeric',
                    hour12: true
                };
                timeElement.textContent = now.toLocaleDateString('en-US', options);
            }
        }

        // Update time every second
        setInterval(updateTime, 1000);
        updateTime(); // Initial call
    </script>
</body>

</html>
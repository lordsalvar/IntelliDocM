<?php
session_start();
require_once 'database.php';

// Add this function at the top of your PHP code
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

// Update the proposals query to include duration calculation
$proposals_query = "SELECT 
    ap.*, 
    DATE_FORMAT(ap.activity_date, '%b %d') as formatted_date,
    DATE_FORMAT(ap.submitted_date, '%M %d, %Y') as submission_date,
    DATEDIFF(ap.end_activity_date, ap.activity_date) as duration,
    u.full_name as submitted_by
    FROM activity_proposals ap
    LEFT JOIN users u ON ap.user_id = u.id 
    ORDER BY ap.submitted_date DESC 
    LIMIT 5";
$proposals_result = $conn->query($proposals_query);

// Fetch system statistics
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetch_row()[0],
    'total_proposals' => $conn->query("SELECT COUNT(*) FROM activity_proposals")->fetch_row()[0],
    'pending_proposals' => $conn->query("SELECT COUNT(*) FROM activity_proposals WHERE status = 'pending'")->fetch_row()[0],
    'total_clubs' => $conn->query("SELECT COUNT(*) FROM clubs")->fetch_row()[0]
];

// Update the facility usage query to show monthly stats
$facility_usage = $conn->query("
    SELECT 
        f.name,
        COUNT(b.id) as booking_count,
        DATE_FORMAT(b.booking_date, '%Y-%m') as month
    FROM facilities f
    LEFT JOIN bookings b ON f.id = b.facility_id
    WHERE b.booking_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY f.id, DATE_FORMAT(b.booking_date, '%Y-%m')
    ORDER BY month DESC, booking_count DESC
")->fetch_all(MYSQLI_ASSOC);

$monthly_stats = $conn->query("
    SELECT 
        DATE_FORMAT(submitted_date, '%Y-%m') as month,
        COUNT(*) as total_proposals,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as rejected
    FROM activity_proposals
    WHERE submitted_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(submitted_date, '%Y-%m')
    ORDER BY month DESC
")->fetch_all(MYSQLI_ASSOC);

// Add this query after your existing stats queries
$recent_utilization = $conn->query("
    SELECT 
        f.name as facility_name,
        c.club_name,
        b.booking_date,
        COUNT(DISTINCT br.room_id) as rooms_used,
        b.status
    FROM bookings b
    JOIN facilities f ON b.facility_id = f.id
    LEFT JOIN booking_rooms br ON b.id = b.id
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN club_memberships cm ON u.id = cm.user_id
    LEFT JOIN clubs c ON cm.club_id = c.club_id
    WHERE b.booking_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
    GROUP BY b.id
    ORDER BY b.booking_date DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Update the calendar events query to only show confirmed activities
$mini_calendar_events = $conn->query("
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
    AND status = 'confirmed'  /* Add this condition */
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
    WHERE booking_date >= CURRENT_DATE 
    AND booking_date <= DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)
    AND b.status = 'Confirmed'  /* Add this condition - note the capital C */
    GROUP BY b.id
    ORDER BY start ASC
    LIMIT 10")->fetch_all(MYSQLI_ASSOC);

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
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            line-height: 1.6;
        }

        .dashboard-main-content {
            padding: 0 2rem 2rem;
            max-width: 1600px;
            margin: 0 auto;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin: 0 2rem 2rem;
        }

        .welcome-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #8B0000;
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

        .dashboard-charts {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            height: 360px;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
        }

        .chart-header {
            padding: 0.75rem 0;
            /* Reduced padding */
            margin-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }

        .chart-header h3 {
            font-size: 1rem;
            /* Smaller font */
            margin: 0;
            color: #333;
        }

        .chart-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            /* Reduced padding */
            position: relative;
            height: calc(100% - 50px);
            /* Subtract header height */
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chart-content canvas {
            max-width: 100%;
            max-height: 100%;
        }

        .recent-proposals {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 1200px) {
            .dashboard-charts {
                grid-template-columns: 1fr;
            }

            .welcome-card,
            .stats-grid {
                margin: 1.5rem;
            }

            .dashboard-main-content {
                padding: 0 1.5rem 1.5rem;
            }

            .chart-card {
                height: 340px;
                /* Even smaller on tablets */
            }
        }

        @media (max-width: 768px) {

            .welcome-card,
            .stats-grid {
                margin: 1rem;
            }

            .dashboard-main-content {
                padding: 0 1rem 1rem;
            }

            .chart-card {
                height: 300px;
                /* Smallest on mobile */
            }
        }

        .utilization-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .utilization-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .usage-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            border: 1px solid #e9ecef;
        }

        .usage-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .usage-header h4 {
            margin: 0;
            font-size: 1rem;
            color: #333;
        }

        .usage-details {
            font-size: 0.9rem;
            color: #666;
        }

        .usage-details p {
            margin: 0.25rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-header .subtitle {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        /* Recent Proposals Styling */
        .recent-proposals {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .view-all {
            color: #1976d2;
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .proposals-grid {
            display: grid;
            gap: 1rem;
        }

        .proposal-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            border: 1px solid #eee;
            transition: transform 0.2s;
        }

        /* ... Add all the styles from proposals.php for .proposal-header, .proposal-content, etc ... */

        /* Additional specific styles for dashboard */
        .recent-proposals .proposal-card {
            margin-bottom: 1rem;
        }

        .recent-proposals .proposal-meta {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }

        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge.received {
            background: #e3f2fd;
            color: #1976d2;
        }

        .status-badge.pending {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-badge.confirmed {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-badge.cancelled {
            background: #ffebee;
            color: #c62828;
        }

        /* Recent Proposals Styling to match proposals.php */
        .proposal-card {
            background: white;
            border-radius: 12px;
            padding: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
            border: 1px solid #eee;
            margin-bottom: 1rem;
        }

        .proposal-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .proposal-header {
            padding: 1.25rem;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .proposal-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }

        .proposal-content {
            padding: 1.25rem;
        }

        .proposal-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            font-size: 0.9rem;
        }

        .meta-item i {
            width: 16px;
            text-align: center;
            color: #1976d2;
        }

        .proposal-footer {
            padding: 1rem 1.25rem;
            background: #f8f9fa;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge i {
            font-size: 0.8rem;
        }

        .status-badge.received {
            background: #e3f2fd;
            color: #1976d2;
        }

        .status-badge.pending {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-badge.confirmed {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-badge.cancelled {
            background: #ffebee;
            color: #c62828;
        }

        .designation {
            color: #1976d2;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .btn-action {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .recent-proposals {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .header-left .subtitle {
            color: #666;
            font-size: 0.9rem;
            margin: 0.25rem 0 0 0;
        }

        .proposals-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .proposal-item {
            display: flex;
            gap: 1.5rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .proposal-item:hover {
            transform: translateX(5px);
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .proposal-date {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 60px;
            padding: 0.5rem;
            background: #e3f2fd;
            border-radius: 6px;
            color: #1976d2;
        }

        .proposal-date .date {
            font-weight: 600;
            text-align: center;
            line-height: 1.2;
        }

        .proposal-main {
            flex: 1;
        }

        .proposal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .proposal-header h4 {
            margin: 0;
            font-size: 1.1rem;
            color: #2c3e50;
        }

        .proposal-details {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #666;
        }

        .detail-item i {
            color: #1976d2;
            width: 16px;
            text-align: center;
        }

        .btn-action {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            background: #e3f2fd;
            color: #1976d2;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-action:hover {
            background: #1976d2;
            color: white;
        }

        .status-badge {
            font-size: 0.85rem;
            padding: 0.35rem 0.75rem;
            border-radius: 15px;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .status-badge i {
            font-size: 0.7rem;
        }

        /* Add this CSS to your existing styles */
        .calendar-widget {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
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
            padding: 0.75rem;
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
            min-width: 60px;
            padding: 0.5rem;
            border-radius: 6px;
            background: #e3f2fd;
            color: #1976d2;
        }

        .event-date i {
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }

        .event-details {
            flex: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .event-details h4 {
            margin: 0;
            font-size: 0.95rem;
            color: #2c3e50;
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

        /* Status colors */
        .event-item.activity.confirmed .event-status,
        .event-item.booking.confirmed .event-status {
            color: #2e7d32;
        }

        .event-item.pending .event-status {
            color: #f57c00;
        }

        .event-item.cancelled .event-status {
            color: #c62828;
        }

        .event-item.activity .event-date {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .event-item.booking .event-date {
            background: #fff3e0;
            color: #f57c00;
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

        .event-date {
            min-width: 80px;
            /* Increased width for date range */
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

        .event-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
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

        .event-item {
            padding: 1rem;
        }

        .event-details {
            align-items: flex-start;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="content">

            <!-- Replace the welcome card section with this -->
            <div class="welcome-card">
                <?php
                $timeInfo = getTimeBasedGreeting();
                $currentTime = date('F j, Y, g:i a');
                ?>
                <div class="welcome-header">
                    <div class="welcome-text">
                        <h2><?= $timeInfo['greeting'] ?>, <?= htmlspecialchars($admin_data['full_name']); ?>
                            <i class="fas fa-<?= $timeInfo['icon'] ?> <?= $timeInfo['icon'] ?>-icon"></i>
                        </h2>
                        <span class="current-time"><?= $currentTime ?></span>
                    </div>
                </div>
                <p class="motto">Ametur Cor Jesu, Ametur Cor Mariae!</p>
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

            <!-- Updated Layout Structure -->
            <div class="dashboard-main-content">
                <!-- Charts Section -->
                <div class="dashboard-charts">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3><i class="fas fa-chart-pie"></i> Facility Usage</h3>
                        </div>
                        <div class="chart-content">
                            <canvas id="facilityUsageChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-header">
                            <h3><i class="fas fa-chart-line"></i> Activity Trends</h3>
                        </div>
                        <div class="chart-content">
                            <canvas id="monthlyTrendsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Add this HTML after your charts section and before the utilization section -->
                <div class="calendar-widget">
                    <div class="section-header">
                        <div class="header-left">
                            <h3><i class="fas fa-calendar-alt"></i> Upcoming Activities</h3>
                            <p class="subtitle">Next 7 days schedule</p>
                        </div>
                        <a href="admin_activity_calendar.php" class="view-all">
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

                <!-- Add this HTML after the charts section and before recent proposals -->
                <div class="utilization-section">
                    <div class="section-header">
                        <h3><i class="fas fa-history"></i> Recent Facility Utilization</h3>
                        <span class="subtitle">Last 30 days usage history</span>
                    </div>
                    <div class="utilization-grid">
                        <?php foreach ($recent_utilization as $usage): ?>
                            <div class="usage-card">
                                <div class="usage-header">
                                    <h4><?= htmlspecialchars($usage['facility_name']) ?></h4>
                                    <span class="status-badge <?= strtolower($usage['status']) ?>">
                                        <?= htmlspecialchars($usage['status']) ?>
                                    </span>
                                </div>
                                <div class="usage-details">
                                    <p><i class="fas fa-users"></i> <?= htmlspecialchars($usage['club_name'] ?? 'Individual Booking') ?></p>
                                    <p><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($usage['booking_date'])) ?></p>
                                    <p><i class="fas fa-door-open"></i> <?= $usage['rooms_used'] ?> room(s) used</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Replace the recent proposals section with this -->
                <div class="recent-proposals">
                    <div class="section-header">
                        <div class="header-left">
                            <h3><i class="fas fa-clipboard-list"></i> Recent Activity Proposals</h3>
                            <p class="subtitle">Latest activity proposals submitted to the system</p>
                        </div>
                        <a href="admin/proposals.php" class="view-all">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>

                    <?php if ($proposals_result->num_rows === 0): ?>
                        <div class="empty-state">
                            <i class="fas fa-file-alt"></i>
                            <h3>No Recent Proposals</h3>
                            <p>There are no activity proposals submitted yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="proposals-list">
                            <?php while ($proposal = $proposals_result->fetch_assoc()): ?>
                                <div class="proposal-item" data-status="<?= strtolower($proposal['status']) ?>">
                                    <div class="proposal-date">
                                        <?php if ($proposal['duration'] > 0): ?>
                                            <span class="date-range">
                                                <?= date('M d', strtotime($proposal['activity_date'])) ?> -
                                                <?= date('M d', strtotime($proposal['end_activity_date'])) ?>
                                            </span>
                                            <span class="duration"><?= $proposal['duration'] + 1 ?> days</span>
                                        <?php else: ?>
                                            <span class="date"><?= $proposal['formatted_date'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="proposal-main">
                                        <div class="proposal-header">
                                            <div class="header-info">
                                                <h4><?= htmlspecialchars($proposal['activity_title']) ?></h4>
                                                <?php if ($proposal['duration'] > 0): ?>
                                                    <span class="duration-badge">
                                                        <i class="fas fa-clock"></i>
                                                        <?= $proposal['duration'] + 1 ?> day<?= $proposal['duration'] > 0 ? 's' : '' ?> activity
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="status-badge <?= strtolower($proposal['status']) ?>">
                                                <i class="fas fa-circle"></i>
                                                <?= ucfirst($proposal['status']) ?>
                                            </span>
                                        </div>
                                        <div class="proposal-details">
                                            <div class="detail-item">
                                                <i class="fas fa-users"></i>
                                                <span><?= htmlspecialchars($proposal['club_name']) ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span><?= htmlspecialchars($proposal['venue']) ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-user"></i>
                                                <span><?= htmlspecialchars($proposal['submitted_by']) ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-clock"></i>
                                                <span>Submitted <?= $proposal['submission_date'] ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="proposal-actions">
                                        <a href="admin/view_proposal.php?id=<?= $proposal['proposal_id'] ?>"
                                            class="btn-action" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Update chart configurations
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    align: 'start',
                    labels: {
                        boxWidth: 10,
                        padding: 8,
                        font: {
                            size: 10
                        }
                    }
                }
            }
        };

        // Specific options for pie chart
        const pieOptions = {
            ...chartOptions,
            aspectRatio: 1.4,
            plugins: {
                ...chartOptions.plugins,
                legend: {
                    position: 'bottom',
                    align: 'start',
                    labels: {
                        boxWidth: 12,
                        padding: 8,
                        font: {
                            size: 10
                        }
                    }
                }
            },
            layout: {
                padding: {
                    top: 10,
                    bottom: 10,
                    left: 10,
                    right: 10
                }
            }
        };

        // Update the chart data processing
        const facilityData = <?php echo json_encode($facility_usage); ?>;
        const facilityChartData = {
            labels: [...new Set(facilityData.map(item => item.name))],
            datasets: [{
                data: facilityData.map(item => item.booking_count),
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'
                ]
            }]
        };

        new Chart(document.getElementById('facilityUsageChart'), {
            type: 'doughnut',
            data: facilityChartData,
            options: pieOptions
        });

        // Monthly Trends Chart
        const monthlyData = <?php echo json_encode($monthly_stats); ?>;
        new Chart(document.getElementById('monthlyTrendsChart'), {
            type: 'line',
            data: {
                labels: monthlyData.map(item => {
                    const [year, month] = item.month.split('-');
                    return new Date(year, month - 1).toLocaleDateString('default', {
                        month: 'short'
                    });
                }),
                datasets: [{
                    label: 'Total',
                    data: monthlyData.map(item => item.total_proposals),
                    borderColor: '#4BC0C0',
                    tension: 0.3
                }, {
                    label: 'Approved',
                    data: monthlyData.map(item => item.approved),
                    borderColor: '#36A2EB',
                    tension: 0.3
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                size: 10
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });

        // Add this JavaScript to update time
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
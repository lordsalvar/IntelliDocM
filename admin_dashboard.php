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
            margin: 2rem;
            margin-bottom: 2rem;
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
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

                <!-- Recent Proposals Section -->
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
    </script>
</body>

</html>
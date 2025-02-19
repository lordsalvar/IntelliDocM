<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dean Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/activity_logger.js" defer></script>
    <style>
        #activityChart {
            max-width: 800px;
            max-height: 600px;
            display: block;
            margin: auto;
        }
    </style>
</head>

<body>
    <div class="dashboard">
        <?php include 'includes/sidebar.php'; ?>
        <div class="content">
            <div class="card">
                <h2>Welcome, Dean</h2>
                <p>This is your dashboard where you can review and manage activity proposals.</p>
            </div>
            <div class="card">
                <h3>Recent Documents</h3>
                <div class="recent-documents">
                    <ul>
                        <li><i class="fas fa-file-alt"></i> <a href="#">Proposal for Intramurals</a></li>
                        <li><i class="fas fa-file-alt"></i> <a href="#">Budget Report for Intramurals</a></li>
                        <li><i class="fas fa-file-alt"></i> <a href="#">Proposal for IT WEEK</a></li>
                        <li><i class="fas fa-file-alt"></i> <a href="#">Minutes of the Last CCIS Meeting</a></li>
                        <li><i class="fas fa-file-alt"></i> <a href="#">Club Fundraising Proposal</a></li>
                    </ul>
                </div>
            </div>
            <div class="card">
                <h3>Recent Club Activities</h3>
                <canvas id="activityChart"></canvas>
            </div>
            <div class="card">
                <h3>Quick Actions</h3>
                <div class="quick-actions" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                    <button style="padding: 1rem; border: none;">
                        <i class="fas fa-folder"></i> View All Proposals
                    </button>
                </div>
            </div>
            <div class="proposals-card">
                <div class="proposals-header">
                    <h3 class="proposals-title">
                        <i class="fas fa-clipboard-list"></i>
                        Recent Activity Proposals
                    </h3>
                </div>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <p>No activity proposals found.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('activityChart').getContext('2d');
        const activityChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['IT week', 'Intramurals', 'Mini Olympics'],
                datasets: [{
                    label: 'Number of Participants',
                    data: [120, 190, 100, 10, 15],
                    backgroundColor: [
                                'rgba(255, 99, 132, 0.6)',
                                'rgba(54, 162, 235, 0.6)',
                                'rgba(255, 206, 86, 0.6)',
                                'rgba(75, 192, 192, 0.6)',
                                'rgba(153, 102, 255, 0.6)'
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)'
                            ],
                            borderWidth: 1
                        }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>

</html>

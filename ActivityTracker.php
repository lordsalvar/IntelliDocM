<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Activities Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/actTrack.css">
    <!-- Ensure Chart.js is loaded from CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<body>
    <header>
        <?php include 'includes/clientnavbar.php'; ?>
    </header>
    <div class="container dashboard-container">
    <h1 class="text-center">Activity Tracker Dashboard</h1>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Top 5 Clubs based on number of activities</div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">Literature Club - 18 activities</li>
                        <li class="list-group-item">Chess Club - 15 activities</li>
                        <li class="list-group-item">Robotics Club - 12 activities</li>
                        <li class="list-group-item">Debate Club - 11 activities</li>
                        <li class="list-group-item">Science Club - 10 activities</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Top 5 Active Clubs based on hours logged</div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">Science Club - 150 hours</li>
                        <li class="list-group-item">Math Club - 145 hours</li>
                        <li class="list-group-item">Technology Club - 130 hours</li>
                        <li class="list-group-item">History Club - 120 hours</li>
                        <li class="list-group-item">Art Club - 110 hours</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Club Activities Over Time</div>
                    <div class="row">
                        <div class="col-md-8">
                            <canvas id="activitiesChart"></canvas>
                        </div>
                        <div class="col-md-4 list-container">
                            <ul class="list-group">
                                <li class="list-group-item">Robotics Club - Monthly Meeting</li>
                                <li class="list-group-item">Chess Club - Championship</li>
                                <li class="list-group-item">Debate Club - Weekly Debate</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Project Completion Rates</div>
                    <canvas id="projectCompletionChart" class="chart-container"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Top Assigned Projects</div>
                    <div class="list-container">
                        <ul class="list-group">
                            <li class="list-group-item">Project Alpha - 250 hours</li>
                            <li class="list-group-item">Project Beta - 200 hours</li>
                            <li class="list-group-item">Project Gamma - 180 hours</li>
                            <li class="list-group-item">Project Delta - 150 hours</li>
                            <li class="list-group-item">Project Epsilon - 120 hours</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Detailed Activities Report</div>
                    <div class="row">
                        <div class="col-md-8">
                            <canvas id="detailedActivitiesChart"></canvas>
                        </div>
                        <div class="col-md-4 list-container">
                            <ul class="list-group">
                                <li class="list-group-item">Jan - New Year Kickoff</li>
                                <li class="list-group-item">Feb - Valentine's Day Event</li>
                                <li class="list-group-item">Mar - Spring Festival</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer>
        <?php include 'includes/footer.php'; ?>
    </footer>

    <script>
        // Ensure the script loads after the DOM to prevent issues
        document.addEventListener('DOMContentLoaded', function () {
            var ctxActivities = document.getElementById('activitiesChart').getContext('2d');
            var activitiesChart = new Chart(ctxActivities, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Number of Activities',
                        data: [5, 10, 15, 20, 25, 30],
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.6)',
                            'rgba(54, 162, 235, 0.6)',
                            'rgba(255, 206, 86, 0.6)',
                            'rgba(75, 192, 192, 0.6)',
                            'rgba(153, 102, 255, 0.6)',
                            'rgba(255, 159, 64, 0.6)'
                        ]
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            var ctxProjectCompletion = document.getElementById('projectCompletionChart').getContext('2d');
            var projectCompletionChart = new Chart(ctxProjectCompletion, {
                type: 'bar',
                data: {
                    labels: ['Innovative Tech', 'TechAdvantage Software', 'Coastal Shipping', 'Green Gardens', 'City Construction', 'Urban Apparel', 'Global Exports Co.', 'Solar Solutions'],
                    datasets: [{
                        label: 'Completion Rate',
                        data: [3000, 2600, 1800, 1300, 1200, 900, 600, 300],
                        backgroundColor: 'rgba(255, 99, 132, 0.6)'
                    }]
                },
                options: {
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            var ctxDetailed = document.getElementById('detailedActivitiesChart').getContext('2d');
            var detailedActivitiesChart = new Chart(ctxDetailed, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Hours Logged',
                        data: [50, 60, 70, 80, 90, 100],
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2,
                        fill: true
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
